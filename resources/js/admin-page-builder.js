/**
 * Admin page builder (Alpine component).
 *
 * State is a plain array of sections { id, type, enabled, data }. The schema handed
 * over by the server (SectionSchema::forEditor) drives which inputs each block shows,
 * so adding a block type only needs a PHP schema entry + a Blade render component.
 */
const clone = (value) => JSON.parse(JSON.stringify(value));
const rid = () => 's' + Math.random().toString(36).slice(2, 9);

export function registerPageBuilder(Alpine) {
    Alpine.data('pageBuilder', (config) => {
        // Option maps arrive as {value: label}; lists are friendlier for x-for.
        const prepareField = (field) => ({
            ...field,
            optList: field.options ? Object.entries(field.options).map(([value, label]) => ({ value, label })) : null,
            fields: field.fields ? field.fields.map(prepareField) : undefined,
        });

        const schema = config.schema.map((def) => ({ ...def, fields: def.fields.map(prepareField) }));

        return {
            sections: config.sections.map((s) => ({ ...clone(s), open: false })),
            schema,
            byType: Object.fromEntries(schema.map((d) => [d.type, d])),
            categories: config.categories,
            icons: config.icons,
            uploadUrl: config.uploadUrl,
            storageBase: config.storageBase,
            adding: false,
            dirty: false,
            confirmDelete: null,
            uploading: {},
            uploadError: '',
            payload: '',

            init() {
                // Mark dirty on any edit made after the initial render.
                // Watch only real content — expanding/collapsing a card must not count as an edit.
                this.$nextTick(() => {
                    this.$watch(() => this.snapshot(), () => { this.dirty = true; });
                });

                window.addEventListener('beforeunload', (event) => {
                    if (this.dirty) {
                        event.preventDefault();
                        event.returnValue = '';
                    }
                });
            },

            snapshot() {
                return JSON.stringify(this.sections.map(({ id, type, enabled, data }) => ({ id, type, enabled, data })));
            },

            def(type) {
                return this.byType[type];
            },

            typeLabel(section) {
                return this.def(section.type)?.label ?? section.type;
            },

            summary(section) {
                const d = section.data;
                const text = d.title || d.eyebrow || d.text || d.heading || (d.items && d.items[0] && (d.items[0].title || d.items[0].q || d.items[0].name || d.items[0].label)) || '';
                return String(text).replace(/<[^>]*>/g, '').slice(0, 70);
            },

            imageUrl(value) {
                if (!value) return '';
                return /^(https?:)?\/\//.test(value) || value.startsWith('/') ? value : this.storageBase + value;
            },

            // ---- sections ----
            addSection(type) {
                const def = this.def(type);
                this.sections.push({ id: rid(), type, enabled: true, data: clone(def.blank), open: true });
                this.adding = false;
                this.$nextTick(() => {
                    const cards = this.$root.querySelectorAll('[data-section-card]');
                    cards[cards.length - 1]?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            },

            move(index, direction) {
                const target = index + direction;
                if (target < 0 || target >= this.sections.length) return;
                const [item] = this.sections.splice(index, 1);
                this.sections.splice(target, 0, item);
            },

            duplicate(index) {
                const copy = clone(this.sections[index]);
                copy.id = rid();
                copy.open = true;
                this.sections.splice(index + 1, 0, copy);
            },

            remove(index) {
                this.sections.splice(index, 1);
                this.confirmDelete = null;
            },

            collapseAll(open = false) {
                this.sections.forEach((s) => { s.open = open; });
            },

            // ---- repeater rows ----
            addItem(data, field) {
                const row = {};
                field.fields.forEach((f) => { row[f.key] = f.default ?? ''; });
                data[field.key].push(row);
            },

            moveItem(list, index, direction) {
                const target = index + direction;
                if (target < 0 || target >= list.length) return;
                const [item] = list.splice(index, 1);
                list.splice(target, 0, item);
            },

            itemLabel(item, field) {
                return item[field.item_label] || '';
            },

            // ---- images ----
            async upload(event, container, key) {
                const file = event.target.files[0];
                event.target.value = '';
                if (!file) return;

                const token = rid();
                this.uploading[token] = true;
                this.uploadError = '';

                try {
                    const body = new FormData();
                    body.append('image', file);
                    const response = await window.axios.post(this.uploadUrl, body);
                    container[key] = response.data.path;
                } catch (error) {
                    this.uploadError = error.response?.data?.errors?.image?.[0] || 'Upload failed. Use a JPG, PNG or WebP under 5 MB.';
                } finally {
                    delete this.uploading[token];
                }
            },

            isUploading() {
                return Object.keys(this.uploading).length > 0;
            },

            // ---- rich text helper toolbar ----
            format(event, kind) {
                const wrap = event.currentTarget.closest('[data-rich-text]');
                const area = wrap.querySelector('textarea');
                const start = area.selectionStart;
                const end = area.selectionEnd;
                const selected = area.value.slice(start, end) || 'text';
                const wrappers = {
                    bold: ['<strong>', '</strong>'],
                    italic: ['<em>', '</em>'],
                    heading: ['<h3>', '</h3>'],
                    paragraph: ['<p>', '</p>'],
                    list: ['<ul>\n  <li>', '</li>\n  <li>Next item</li>\n</ul>'],
                };
                let before;
                let after;

                if (kind === 'link') {
                    const url = window.prompt('Link address (e.g. /products or https://…)', '/');
                    if (!url) return;
                    before = `<a href="${url.replace(/"/g, '')}">`;
                    after = '</a>';
                } else {
                    [before, after] = wrappers[kind];
                }

                area.value = area.value.slice(0, start) + before + selected + after + area.value.slice(end);
                area.dispatchEvent(new Event('input', { bubbles: true }));
                area.focus();
            },

            // ---- submit ----
            prepare(event) {
                if (this.isUploading()) {
                    event.preventDefault();
                    this.uploadError = 'Please wait for the image upload to finish.';
                    return;
                }

                this.payload = this.snapshot();
                this.dirty = false;
            },
        };
    });
}
