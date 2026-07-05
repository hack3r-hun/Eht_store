<?php

namespace Tests\Feature;

use App\Mail\ContactMessageReplyMail;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'customer']);
        Role::create(['name' => 'admin']);
    }

    public function test_contact_form_saves_message(): void
    {
        $response = $this->post(route('contact.store'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Product inquiry',
            'message' => 'Do you have LED bulbs in stock?',
        ]);

        $response->assertRedirect(route('contact'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'john@example.com',
            'subject' => 'Product inquiry',
        ]);
    }

    public function test_contact_form_validates_required_fields(): void
    {
        $response = $this->from(route('contact'))->post(route('contact.store'), []);

        $response->assertRedirect(route('contact'));
        $response->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
    }

    public function test_admin_can_reply_to_contact_message(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $message = ContactMessage::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Product inquiry',
            'message' => 'Do you have LED bulbs in stock?',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.messages.reply', $message), [
            'message' => 'Yes, LED bulbs are available.',
        ]);

        $response->assertRedirect(route('admin.messages.show', $message));

        $this->assertDatabaseHas('contact_message_replies', [
            'contact_message_id' => $message->id,
            'admin_id' => $admin->id,
            'message' => 'Yes, LED bulbs are available.',
        ]);

        $this->assertNotNull($message->fresh()->last_replied_at);
        $this->assertSame($admin->id, $message->fresh()->last_replied_by);

        Mail::assertQueued(ContactMessageReplyMail::class, function (ContactMessageReplyMail $mail) use ($message) {
            return $mail->hasTo($message->email);
        });
    }

    public function test_admin_can_filter_messages_by_customer_and_reply_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $replied = ContactMessage::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'LED bulbs',
            'message' => 'Need LED bulbs',
            'last_replied_at' => now(),
            'last_replied_by' => $admin->id,
        ]);
        ContactMessage::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subject' => 'Wiring',
            'message' => 'Need wiring',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.messages.index', ['customer' => 'john@example.com']))
            ->assertOk()
            ->assertSee('LED bulbs')
            ->assertDontSee('Wiring');

        $this->actingAs($admin)
            ->get(route('admin.messages.index', ['status' => 'replied']))
            ->assertOk()
            ->assertSee($replied->subject)
            ->assertDontSee('Wiring');
    }

    public function test_non_admin_cannot_reply_to_messages(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $message = ContactMessage::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Product inquiry',
            'message' => 'Do you have LED bulbs in stock?',
        ]);

        $this->actingAs($user)
            ->post(route('admin.messages.reply', $message), ['message' => 'No'])
            ->assertForbidden();

        $this->assertDatabaseCount('contact_message_replies', 0);
    }
}
