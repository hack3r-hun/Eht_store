import Cropper from 'cropperjs';

document.addEventListener('alpine:init', () => {
    Alpine.data('adminImageCropper', (config = {}) => ({
        previews: [],
        pendingFiles: [],
        cropQueue: [],
        errors: [],
        cropper: null,
        cropModalOpen: false,
        cropImageSrc: '',
        currentFile: null,
        currentFileName: 'image.jpg',
        aspectRatio: config.aspectRatio ?? 1,
        maxBytes: config.maxBytes ?? 5 * 1024 * 1024,
        inputName: config.inputName ?? 'images[]',
        multiple: config.multiple !== false,

        notify(message, type = 'success') {
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { message, type },
            }));
        },

        canManageInput() {
            if (typeof DataTransfer === 'undefined') {
                return false;
            }

            try {
                new DataTransfer();

                return true;
            } catch {
                return false;
            }
        },

        handleFileSelect(event) {
            this.errors = [];

            // Without DataTransfer we cannot rebuild the input's file list, so
            // leave the native input untouched and let the server validate.
            if (!this.canManageInput()) {
                return;
            }

            const files = Array.from(event.target.files || []);

            if (!this.multiple) {
                this.previews = [];
                this.pendingFiles = [];
            }

            event.target.value = '';

            for (const file of files) {
                if (!file.type.startsWith('image/')) {
                    this.errors.push(`${file.name}: not an image file.`);
                    continue;
                }

                if (file.size > this.maxBytes) {
                    this.errors.push(`${file.name}: must be ${Math.round(this.maxBytes / 1024 / 1024)}MB or smaller.`);
                    continue;
                }

                this.cropQueue.push(file);
            }

            if (this.errors.length > 0) {
                this.notify(this.errors[0], 'error');
            }

            if (this.cropQueue.length > 0 && !this.cropModalOpen) {
                this.processNextCrop();
            }
        },

        processNextCrop() {
            const file = this.cropQueue.shift();

            if (!file) {
                return;
            }

            this.currentFile = file;
            this.currentFileName = file.name;
            this.cropImageSrc = URL.createObjectURL(file);
            this.cropModalOpen = true;

            this.$nextTick(() => this.initCropper());
        },

        initCropper() {
            const image = this.$refs.cropImage;

            if (!image) {
                this.acceptOriginal();

                return;
            }

            if (this.cropper) {
                this.cropper.destroy();
            }

            try {
                this.cropper = new Cropper(image, {
                    aspectRatio: this.aspectRatio,
                    viewMode: 1,
                    autoCropArea: 1,
                    responsive: true,
                });
            } catch {
                this.acceptOriginal();
            }
        },

        // Fallback when cropping is unavailable: upload the original file as-is.
        acceptOriginal() {
            if (this.currentFile) {
                this.addPendingFile(this.currentFile, URL.createObjectURL(this.currentFile));
                this.notify('Cropping unavailable — original image will be uploaded.', 'info');
            }

            this.closeCropModal();

            if (this.cropQueue.length > 0) {
                this.$nextTick(() => this.processNextCrop());
            }
        },

        addPendingFile(file, previewUrl) {
            if (!this.multiple) {
                this.pendingFiles = [file];
                this.previews = [previewUrl];
            } else {
                this.pendingFiles.push(file);
                this.previews.push(previewUrl);
            }

            this.syncFileInput();
        },

        confirmCrop() {
            if (!this.cropper) {
                this.acceptOriginal();

                return;
            }

            const canvas = this.cropper.getCroppedCanvas({
                maxWidth: 1600,
                maxHeight: 1600,
                imageSmoothingQuality: 'high',
            });

            // Keep the source format so PNG/WebP transparency survives the crop.
            const sourceType = this.currentFile?.type;
            const type = ['image/png', 'image/webp'].includes(sourceType) ? sourceType : 'image/jpeg';
            const extension = { 'image/png': 'png', 'image/webp': 'webp', 'image/jpeg': 'jpg' }[type];

            canvas.toBlob((blob) => {
                if (!blob) {
                    this.acceptOriginal();

                    return;
                }

                const baseName = this.currentFileName.replace(/\.[^.]+$/, '') || 'image';
                const file = new File([blob], `${baseName}.${extension}`, { type });

                this.addPendingFile(file, URL.createObjectURL(blob));
                this.closeCropModal();
                this.notify('Image ready — click Save to upload.', 'success');

                if (this.cropQueue.length > 0) {
                    this.$nextTick(() => this.processNextCrop());
                }
            }, type, type === 'image/png' ? undefined : 0.9);
        },

        syncFileInput() {
            const input = this.$refs.fileInput;

            if (!input) {
                return;
            }

            const transfer = new DataTransfer();
            this.pendingFiles.forEach((file) => transfer.items.add(file));
            input.files = transfer.files;
        },

        removePending(index) {
            this.previews.splice(index, 1);
            this.pendingFiles.splice(index, 1);
            this.syncFileInput();
        },

        closeCropModal() {
            this.cropModalOpen = false;
            this.currentFile = null;

            if (this.cropper) {
                this.cropper.destroy();
                this.cropper = null;
            }

            if (this.cropImageSrc) {
                URL.revokeObjectURL(this.cropImageSrc);
                this.cropImageSrc = '';
            }
        },

        skipCropQueue() {
            this.cropQueue = [];
            this.closeCropModal();
        },
    }));
});
