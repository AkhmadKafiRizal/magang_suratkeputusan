const dashboardShell = document.querySelector('[data-dashboard-shell]');

const profileMenu = document.querySelector('[data-profile-menu]');
if (profileMenu) {
    document.addEventListener('click', (event) => {
        if (!profileMenu.contains(event.target)) profileMenu.open = false;
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && profileMenu.open) {
            profileMenu.open = false;
            profileMenu.querySelector('summary').focus();
        }
    });
    profileMenu.addEventListener('focusout', (event) => {
        if (!profileMenu.contains(event.relatedTarget)) profileMenu.open = false;
    });
}

if (dashboardShell) {
    const openButton = dashboardShell.querySelector('[data-sidebar-open]');
    const closeButtons = dashboardShell.querySelectorAll('[data-sidebar-close]');
    const sidebarLinks = dashboardShell.querySelectorAll('.sidebar-link');
    const sidebarCloseButton = dashboardShell.querySelector('.sidebar-close-button');

    const setSidebarOpen = (isOpen, restoreFocus = false) => {
        dashboardShell.classList.toggle('sidebar-is-open', isOpen);
        openButton?.setAttribute('aria-expanded', String(isOpen));
        document.body.style.overflow = isOpen ? 'hidden' : '';

        if (isOpen) {
            sidebarCloseButton?.focus();
        } else if (restoreFocus) {
            openButton?.focus();
        }
    };

    openButton?.addEventListener('click', () => setSidebarOpen(true));
    closeButtons.forEach((button) => button.addEventListener('click', () => setSidebarOpen(false, true)));

    sidebarLinks.forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 980) {
                setSidebarOpen(false);
            }
        });
    });

    const dropdownToggles = dashboardShell.querySelectorAll('[data-dropdown-toggle]');
    dropdownToggles.forEach((toggle) => {
        toggle.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            const group = toggle.closest('[data-sidebar-dropdown]');
            if (!group) {
                return;
            }
            const isOpen = group.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', String(isOpen));
        });
    });

    const sidebarSublinks = dashboardShell.querySelectorAll('.sidebar-sublink');
    sidebarSublinks.forEach((sublink) => {
        sublink.addEventListener('click', () => {
            if (window.innerWidth <= 980) {
                setSidebarOpen(false);
            }
        });
    });

    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && dashboardShell.classList.contains('sidebar-is-open')) {
            setSidebarOpen(false, true);
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 980) {
            setSidebarOpen(false);
        }
    });
}

document.querySelectorAll('[data-period-filter]').forEach((form) => {
    const periodSelect = form.querySelector('[data-period-select]');
    const monthFilter = form.querySelector('[data-month-filter]');
    const monthSelect = monthFilter?.querySelector('select');
    const movingFields = [
        form.querySelector('[data-period-field]'),
        form.querySelector('[data-year-field]'),
        form.querySelector('.report-filter-actions'),
    ].filter(Boolean);
    const reducedPeriodMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let periodExitTimer = null;

    const syncMonthFilter = (animateLayout = false) => {
        const monthly = periodSelect?.value === 'bulanan';
        const previousPositions = animateLayout
            ? new Map(movingFields.map((field) => [field, field.getBoundingClientRect()]))
            : null;

        if (animateLayout) form.style.transition = 'none';
        form.classList.toggle('is-monthly', monthly);
        form.classList.toggle('is-yearly', !monthly);
        monthFilter?.setAttribute('aria-hidden', String(!monthly));
        if (monthSelect) monthSelect.disabled = !monthly;

        if (!animateLayout) return;

        form.getBoundingClientRect();
        const nextPositions = new Map(movingFields.map((field) => [field, field.getBoundingClientRect()]));
        form.style.removeProperty('transition');

        if (reducedPeriodMotion.matches) return;

        movingFields.forEach((field) => {
            const previous = previousPositions.get(field);
            const next = nextPositions.get(field);
            const offsetX = previous.left - next.left;
            const offsetY = previous.top - next.top;

            if ((!offsetX && !offsetY) || typeof field.animate !== 'function') return;

            field.getAnimations().forEach((animation) => animation.cancel());
            field.animate(
                [
                    { transform: `translate(${offsetX}px, ${offsetY}px)` },
                    { transform: 'translate(0, 0)' },
                ],
                {
                    duration: 320,
                    easing: 'cubic-bezier(.22, 1, .36, 1)',
                },
            );
        });
    };

    periodSelect?.addEventListener('change', () => {
        const monthly = periodSelect.value === 'bulanan';
        window.clearTimeout(periodExitTimer);
        monthFilter?.classList.remove('is-period-exiting');

        if (!monthly && form.classList.contains('is-monthly') && !reducedPeriodMotion.matches) {
            monthFilter?.classList.add('is-period-exiting');
            monthFilter?.setAttribute('aria-hidden', 'true');
            if (monthSelect) monthSelect.disabled = true;
            periodExitTimer = window.setTimeout(() => {
                monthFilter?.classList.remove('is-period-exiting');
                syncMonthFilter(true);
            }, 170);
            return;
        }

        syncMonthFilter(true);
    });
    syncMonthFilter();
});

const fieldClickMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

document.querySelectorAll('[data-field-click-feedback]').forEach((field) => {
    const animateField = () => {
        if (fieldClickMotion.matches || typeof field.animate !== 'function') {
            return;
        }

        field.getAnimations().forEach((animation) => animation.cancel());
        field.animate(
            [
                { transform: 'translateY(6px)', opacity: .72, boxShadow: '0 0 0 1px rgba(35, 116, 209, .05)' },
                { transform: 'translateY(0)', opacity: 1, boxShadow: '0 0 0 3px rgba(35, 116, 209, .11)' },
            ],
            {
                duration: 460,
                easing: 'cubic-bezier(.2, .75, .25, 1)',
            },
        );
    };

    field.addEventListener('pointerdown', animateField);
    field.addEventListener('keydown', (event) => {
        if (['Enter', ' ', 'ArrowDown'].includes(event.key)) {
            animateField();
        }
    });
});

document.querySelectorAll('input[type="date"][data-open-date-picker]').forEach((field) => {
    field.addEventListener('click', () => {
        if (field.disabled || field.readOnly || typeof field.showPicker !== 'function') {
            return;
        }

        try {
            field.showPicker();
        } catch {
            // Browser tetap menggunakan perilaku date picker bawaannya.
        }
    });
});

document.querySelectorAll('[data-user-avatar-image]').forEach((image) => {
    const showFallback = () => {
        image.hidden = true;
        const fallback = image.parentElement?.querySelector('[data-user-avatar-fallback]');

        if (fallback) {
            fallback.hidden = false;
        }
    };

    image.addEventListener('error', showFallback, { once: true });

    if (image.complete && image.naturalWidth === 0) {
        showFallback();
    }
});

const dismissToast = (toast) => {
    if (!toast || toast.classList.contains('is-leaving')) {
        return;
    }

    const container = toast.closest('[data-toast-container]') || toast;

    toast.classList.add('is-leaving');
    container.classList.add('is-leaving');
    window.setTimeout(() => container.remove(), 240);
};

const initializeToast = (toast) => {
    if (!toast || toast.dataset.toastInitialized === 'true') {
        return;
    }

    toast.dataset.toastInitialized = 'true';
    const timeout = Number(toast.dataset.toastTimeout || 4000);
    let timer;

    const startTimer = () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => dismissToast(toast), timeout);
    };

    const pauseTimer = () => window.clearTimeout(timer);

    toast.querySelector('[data-toast-close]')?.addEventListener('click', () => dismissToast(toast));
    toast.addEventListener('mouseenter', pauseTimer);
    toast.addEventListener('mouseleave', startTimer);
    toast.addEventListener('focusin', pauseTimer);
    toast.addEventListener('focusout', (event) => {
        if (!toast.contains(event.relatedTarget)) {
            startTimer();
        }
    });

    startTimer();
};

document.querySelectorAll('[data-toast]').forEach(initializeToast);

const showToast = (type, message) => {
    let region = document.querySelector('[data-toast-region]');

    if (!region) {
        region = document.createElement('div');
        region.className = 'toast-region';
        region.dataset.toastRegion = '';
        region.setAttribute('aria-label', 'Notifikasi');
        document.body.append(region);
    }

    const isError = type === 'error';
    const toast = document.createElement('div');
    toast.className = `app-toast app-toast-${type}`;
    toast.dataset.toast = '';
    toast.dataset.toastTimeout = '4000';
    toast.setAttribute('role', isError ? 'alert' : 'status');
    toast.setAttribute('aria-live', isError ? 'assertive' : 'polite');
    toast.setAttribute('aria-atomic', 'true');

    const icon = document.createElement('span');
    icon.className = 'toast-icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.innerHTML = isError
        ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 17h.01" stroke-linecap="round"/></svg>'
        : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    const copy = document.createElement('span');
    copy.className = 'toast-copy';
    const title = document.createElement('strong');
    title.textContent = isError ? 'Terjadi Kesalahan' : 'Berhasil';
    const body = document.createElement('span');
    body.textContent = message;
    copy.append(title, body);

    const close = document.createElement('button');
    close.className = 'toast-close';
    close.type = 'button';
    close.dataset.toastClose = '';
    close.setAttribute('aria-label', 'Tutup notifikasi');
    close.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m7 7 10 10M17 7 7 17" stroke-linecap="round"/></svg>';

    toast.append(icon, copy, close);
    region.append(toast);
    initializeToast(toast);
};

const replaceButtonLabel = (button, label, loading = false) => {
    if (!button) {
        return;
    }

    const nodes = [];

    if (loading) {
        const spinner = document.createElement('span');
        spinner.className = 'button-spinner';
        spinner.setAttribute('aria-hidden', 'true');
        nodes.push(spinner);
    }

    nodes.push(document.createTextNode(label));
    button.replaceChildren(...nodes);
};

const confirmationModal = document.querySelector('[data-confirmation-modal]');
let confirmationForm = null;
let confirmationSubmitter = null;
let confirmationTrigger = null;
let confirmationLoadingLabel = 'Memproses...';
let confirmationConfirmLabel = 'Ya, Lanjutkan';

const closeConfirmation = (restoreFocus = true) => {
    if (!confirmationModal || confirmationModal.hidden) {
        return;
    }

    const submitButton = confirmationModal.querySelector('[data-confirmation-submit]');

    if (submitButton?.disabled) {
        return;
    }

    confirmationModal.classList.remove('is-open');
    document.body.classList.remove('modal-is-open');
    window.setTimeout(() => {
        confirmationModal.hidden = true;
    }, 200);

    if (restoreFocus) {
        confirmationTrigger?.focus();
    }

    confirmationForm = null;
    confirmationSubmitter = null;
};

const openConfirmation = (form, submitter, config) => {
    if (!confirmationModal) {
        return false;
    }

    confirmationForm = form;
    confirmationSubmitter = submitter;
    confirmationTrigger = submitter || document.activeElement;
    confirmationLoadingLabel = config.loadingLabel || 'Memproses...';
    confirmationConfirmLabel = config.confirmLabel || 'Ya, Lanjutkan';

    const title = confirmationModal.querySelector('[data-confirmation-title]');
    const message = confirmationModal.querySelector('[data-confirmation-message]');
    const cancelButton = confirmationModal.querySelector('[data-confirmation-cancel]');
    const submitButton = confirmationModal.querySelector('[data-confirmation-submit]');

    title.textContent = config.title;
    message.textContent = config.message;
    replaceButtonLabel(cancelButton, config.cancelLabel || 'Batal');
    replaceButtonLabel(submitButton, confirmationConfirmLabel);
    submitButton.classList.toggle('excel-export-button', config.variant === 'excel');
    submitButton.classList.toggle('danger-button', config.variant === 'danger');
    submitButton.disabled = false;
    confirmationModal.hidden = false;
    document.body.classList.add('modal-is-open');
    window.requestAnimationFrame(() => {
        confirmationModal.classList.add('is-open');
        submitButton.focus();
    });

    return true;
};

const confirmationForForm = (form, submitter = null) => {
    const source = submitter?.dataset.confirmTitle ? submitter : form;

    if (source.dataset.confirmTitle) {
        return {
            title: source.dataset.confirmTitle,
            message: source.dataset.confirmMessage,
            confirmLabel: source.dataset.confirmLabel,
            cancelLabel: source.dataset.confirmCancelLabel,
            loadingLabel: source.dataset.confirmLoadingLabel,
            variant: source.dataset.confirmVariant,
        };
    }

    return null;
};

if (confirmationModal) {
    confirmationModal.querySelectorAll('[data-confirmation-close]').forEach((button) => {
        button.addEventListener('click', () => closeConfirmation(true));
    });

    confirmationModal.querySelector('[data-confirmation-submit]')?.addEventListener('click', (event) => {
        if (!confirmationForm) {
            return;
        }

        const button = event.currentTarget;
        button.disabled = true;
        replaceButtonLabel(button, confirmationLoadingLabel, true);
        confirmationForm.dataset.confirmed = 'true';
        const method = (confirmationSubmitter?.getAttribute('formmethod') || confirmationForm.method || 'get').toLowerCase();
        confirmationForm.requestSubmit(confirmationSubmitter || undefined);

        if (method === 'get') {
            window.setTimeout(() => {
                button.disabled = false;
                replaceButtonLabel(button, confirmationConfirmLabel);
                closeConfirmation(true);
            }, 350);
        }
    });

    confirmationModal.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeConfirmation(true);
            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const focusable = [...confirmationModal.querySelectorAll('button:not(:disabled)')];
        const first = focusable[0];
        const last = focusable.at(-1);

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last?.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first?.focus();
        }
    });
}

const updateQuickStatusView = (form, surat) => {
    if (!surat?.status) {
        return;
    }

    const row = form.closest('[data-surat-row]');
    const updatedAt = row?.querySelector('[data-status-updated-at]');

    if (updatedAt && surat.updated_at_label) {
        updatedAt.textContent = surat.updated_at_label;
        updatedAt.setAttribute('datetime', surat.updated_at || '');
    }

    if (surat.status === 'selesai') {
        const badge = document.createElement('span');
        badge.className = 'status-badge status-selesai';
        badge.textContent = 'Selesai';
        form.replaceWith(badge);
        return;
    }

    const button = form.querySelector('.quick-status-button');
    const statusInput = form.querySelector('input[name="status"]');
    const sedangDiproses = surat.status === 'sedang_diproses';
    const label = sedangDiproses ? 'Sedang Diproses' : 'Belum Ditangani';

    form.dataset.currentStatus = surat.status;
    form.dataset.currentLabel = label;
    button?.classList.remove('status-belum_ditangani', 'status-sedang_diproses', 'status-selesai');
    button?.classList.add(`status-${surat.status}`);

    if (statusInput) {
        statusInput.value = sedangDiproses ? 'selesai' : 'sedang_diproses';
    }

    if (button) {
        button.disabled = false;
        button.removeAttribute('aria-busy');
        button.dataset.loadingLabel = sedangDiproses ? 'Menyelesaikan...' : 'Memproses...';
        button.setAttribute('aria-label', `${sedangDiproses ? 'Selesaikan' : 'Mulai proses'} surat ${form.dataset.suratNumber}`);
        replaceButtonLabel(button, label);
    }

    if (sedangDiproses) {
        form.dataset.confirmTitle = 'Konfirmasi Penyelesaian Surat';
        form.dataset.confirmMessage = `Apakah Anda yakin penanganan surat ini sudah selesai? Nomor Surat: ${form.dataset.suratNumber}`;
        form.dataset.confirmLabel = 'Ya, Selesaikan';
        form.dataset.confirmLoadingLabel = 'Menyelesaikan...';
    } else {
        delete form.dataset.confirmTitle;
        delete form.dataset.confirmMessage;
        delete form.dataset.confirmLabel;
        delete form.dataset.confirmLoadingLabel;
    }
};

const finishQuickStatusConfirmation = (form) => {
    if (!confirmationModal || confirmationForm !== form) {
        return;
    }

    const submitButton = confirmationModal.querySelector('[data-confirmation-submit]');
    submitButton.disabled = false;
    replaceButtonLabel(submitButton, confirmationConfirmLabel);
    closeConfirmation(false);
};

const quickStatusErrorMessage = (response, payload) => {
    const validationMessage = payload?.errors
        ? Object.values(payload.errors).flat().find(Boolean)
        : null;

    if (validationMessage) {
        return validationMessage;
    }

    if (response.status === 401 || response.status === 419) {
        return 'Sesi Anda telah berakhir. Silakan muat ulang halaman dan login kembali.';
    }

    if (response.status === 403) {
        return 'Anda tidak memiliki akses untuk mengubah status surat ini.';
    }

    return payload?.message || 'Status surat belum dapat diperbarui. Silakan coba lagi.';
};

const submitQuickStatus = async (form) => {
    const button = form.querySelector('.quick-status-button');

    if (!button || form.dataset.submitting === 'true') {
        return;
    }

    form.dataset.submitting = 'true';
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    replaceButtonLabel(button, button.dataset.loadingLabel || 'Memproses...', true);

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const payload = await response.json().catch(() => ({}));

        if (payload.surat) {
            updateQuickStatusView(form, payload.surat);
        }

        if (!response.ok) {
            throw new Error(quickStatusErrorMessage(response, payload));
        }

        showToast('success', payload.message || 'Status surat berhasil diperbarui.');
    } catch (error) {
        showToast('error', error instanceof Error ? error.message : 'Status surat belum dapat diperbarui. Silakan coba lagi.');
    } finally {
        finishQuickStatusConfirmation(form);
        delete form.dataset.submitting;

        if (form.isConnected) {
            button.disabled = false;
            button.removeAttribute('aria-busy');
            replaceButtonLabel(button, form.dataset.currentLabel || 'Perbarui Status');
        }
    }
};

document.querySelectorAll('[data-profile-photo-form]').forEach((form) => {
    const input = form.querySelector('[data-profile-photo-input]');
    const preview = form.querySelector('[data-profile-photo-preview]');
    const fileName = form.querySelector('[data-profile-photo-file-name]');
    const error = form.querySelector('[data-profile-photo-error]');
    const saveButton = form.querySelector('[data-profile-photo-save]');
    const cancelButton = form.querySelector('[data-profile-photo-cancel]');
    const cropModal = document.querySelector('[data-profile-crop-modal]');
    const cropCanvas = cropModal?.querySelector('[data-profile-crop-canvas]');
    const cropZoom = cropModal?.querySelector('[data-profile-crop-zoom]');
    const cropZoomOutput = cropModal?.querySelector('[data-profile-crop-zoom-output]');
    const cropApplyButton = cropModal?.querySelector('[data-profile-crop-apply]');
    const cropError = cropModal?.querySelector('[data-profile-crop-error]');

    if (
        !input || !preview || !fileName || !error || !saveButton || !cancelButton
        || !cropModal || !cropCanvas || !cropZoom || !cropZoomOutput || !cropApplyButton || !cropError
    ) {
        return;
    }

    const cropContext = cropCanvas.getContext('2d', { alpha: false });

    if (!cropContext) {
        return;
    }

    const savedPreview = [...preview.childNodes].map((node) => node.cloneNode(true));
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    const maximumSize = 2 * 1024 * 1024;
    const outputSize = 512;
    let previewUrl = null;
    let cropSourceUrl = null;
    let cropImage = null;
    let cropZoomValue = 1;
    let cropOffsetX = 0;
    let cropOffsetY = 0;
    let activePointer = null;
    let pointerX = 0;
    let pointerY = 0;
    let appliedCropFile = null;
    let appliedSourceName = '';
    let selectedSourceName = '';
    let imageLoadVersion = 0;
    let cropCloseTimer = null;

    const releasePreviewUrl = () => {
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }
    };

    const releaseCropSource = () => {
        if (cropSourceUrl) {
            URL.revokeObjectURL(cropSourceUrl);
            cropSourceUrl = null;
        }

        cropImage = null;
    };

    const setInputFile = (file) => {
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
    };

    const restoreSavedPreview = () => {
        releasePreviewUrl();
        preview.replaceChildren(...savedPreview.map((node) => node.cloneNode(true)));
        fileName.textContent = 'Belum ada foto baru yang dipilih.';
        error.textContent = '';
        error.hidden = true;
        input.removeAttribute('aria-invalid');
        saveButton.disabled = true;
        cancelButton.hidden = true;
    };

    const showAppliedPreview = () => {
        if (!appliedCropFile) {
            input.value = '';
            restoreSavedPreview();
            return;
        }

        setInputFile(appliedCropFile);
        releasePreviewUrl();
        previewUrl = URL.createObjectURL(appliedCropFile);

        const image = document.createElement('img');
        image.src = previewUrl;
        image.alt = `Preview hasil crop foto profil: ${appliedSourceName}`;
        preview.replaceChildren(image);
        fileName.textContent = `Hasil crop siap disimpan: ${appliedSourceName}`;
        error.textContent = '';
        error.hidden = true;
        input.removeAttribute('aria-invalid');
        saveButton.disabled = false;
        cancelButton.hidden = false;
    };

    const resetSelection = () => {
        appliedCropFile = null;
        appliedSourceName = '';
        input.value = '';
        restoreSavedPreview();
    };

    const rejectFile = (message) => {
        resetSelection();
        error.textContent = message;
        error.hidden = false;
        input.setAttribute('aria-invalid', 'true');
        fileName.textContent = 'Foto belum dipilih.';
    };

    const cropGeometry = () => {
        if (!cropImage) {
            return null;
        }

        const baseScale = Math.max(outputSize / cropImage.naturalWidth, outputSize / cropImage.naturalHeight);
        const scale = baseScale * cropZoomValue;
        const width = cropImage.naturalWidth * scale;
        const height = cropImage.naturalHeight * scale;
        const maximumOffsetX = Math.max(0, (width - outputSize) / 2);
        const maximumOffsetY = Math.max(0, (height - outputSize) / 2);

        cropOffsetX = Math.max(-maximumOffsetX, Math.min(maximumOffsetX, cropOffsetX));
        cropOffsetY = Math.max(-maximumOffsetY, Math.min(maximumOffsetY, cropOffsetY));

        return {
            width,
            height,
            x: (outputSize - width) / 2 + cropOffsetX,
            y: (outputSize - height) / 2 + cropOffsetY,
        };
    };

    const renderCrop = () => {
        const geometry = cropGeometry();

        if (!geometry || !cropImage) {
            return;
        }

        cropContext.fillStyle = '#ffffff';
        cropContext.fillRect(0, 0, outputSize, outputSize);
        cropContext.drawImage(cropImage, geometry.x, geometry.y, geometry.width, geometry.height);
        cropZoomOutput.textContent = `${Math.round(cropZoomValue * 100)}%`;
    };

    const closeCropModal = (focusTarget = input) => {
        window.clearTimeout(cropCloseTimer);
        cropModal.classList.remove('is-open');
        document.body.classList.remove('modal-is-open');
        cropCloseTimer = window.setTimeout(() => {
            cropModal.hidden = true;
        }, 200);
        focusTarget?.focus();
    };

    const openCropModal = () => {
        window.clearTimeout(cropCloseTimer);
        cropModal.hidden = false;
        document.body.classList.add('modal-is-open');
        window.requestAnimationFrame(() => {
            cropModal.classList.add('is-open');
            cropCanvas.focus();
        });
    };

    const cancelCrop = () => {
        imageLoadVersion += 1;
        releaseCropSource();
        closeCropModal(input);
        showAppliedPreview();
    };

    const loadCropImage = (file) => {
        const currentVersion = ++imageLoadVersion;
        releaseCropSource();
        cropSourceUrl = URL.createObjectURL(file);

        const image = new Image();
        image.decoding = 'async';
        image.onload = () => {
            if (currentVersion !== imageLoadVersion) {
                return;
            }

            if (!image.naturalWidth || !image.naturalHeight) {
                releaseCropSource();
                rejectFile('Foto tidak dapat dibaca. Silakan pilih file gambar lain.');
                return;
            }

            cropImage = image;
            cropZoomValue = 1;
            cropOffsetX = 0;
            cropOffsetY = 0;
            cropZoom.value = '1';
            cropError.textContent = '';
            cropError.hidden = true;
            renderCrop();
            openCropModal();
        };
        image.onerror = () => {
            if (currentVersion !== imageLoadVersion) {
                return;
            }

            releaseCropSource();
            rejectFile('Foto rusak atau tidak dapat diproses. Silakan pilih foto lain.');
        };
        image.src = cropSourceUrl;
    };

    input.addEventListener('change', () => {
        const file = input.files?.[0];

        if (!file) {
            return;
        }

        if (!allowedTypes.includes(file.type)) {
            rejectFile('Gunakan foto JPG, PNG, atau WebP.');
            return;
        }

        if (file.size > maximumSize) {
            rejectFile('Ukuran foto maksimal 2 MB.');
            return;
        }

        selectedSourceName = file.name;
        input.value = '';
        fileName.textContent = `Atur crop untuk: ${selectedSourceName}`;
        error.textContent = '';
        error.hidden = true;
        input.removeAttribute('aria-invalid');
        saveButton.disabled = true;
        loadCropImage(file);
    });

    cancelButton.addEventListener('click', () => {
        imageLoadVersion += 1;
        releaseCropSource();
        resetSelection();
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.focus();
    });

    cropZoom.addEventListener('input', () => {
        cropZoomValue = Number(cropZoom.value);
        renderCrop();
    });

    cropCanvas.addEventListener('pointerdown', (event) => {
        activePointer = event.pointerId;
        pointerX = event.clientX;
        pointerY = event.clientY;
        cropCanvas.setPointerCapture(event.pointerId);
        event.preventDefault();
    });

    cropCanvas.addEventListener('pointermove', (event) => {
        if (activePointer !== event.pointerId) {
            return;
        }

        const bounds = cropCanvas.getBoundingClientRect();
        const ratio = outputSize / bounds.width;
        cropOffsetX += (event.clientX - pointerX) * ratio;
        cropOffsetY += (event.clientY - pointerY) * ratio;
        pointerX = event.clientX;
        pointerY = event.clientY;
        renderCrop();
        event.preventDefault();
    });

    const stopDragging = (event) => {
        if (activePointer !== event.pointerId) {
            return;
        }

        if (cropCanvas.hasPointerCapture(event.pointerId)) {
            cropCanvas.releasePointerCapture(event.pointerId);
        }

        activePointer = null;
    };

    cropCanvas.addEventListener('pointerup', stopDragging);
    cropCanvas.addEventListener('pointercancel', stopDragging);

    cropCanvas.addEventListener('keydown', (event) => {
        const movement = event.shiftKey ? 24 : 8;

        if (event.key === 'ArrowLeft') cropOffsetX -= movement;
        else if (event.key === 'ArrowRight') cropOffsetX += movement;
        else if (event.key === 'ArrowUp') cropOffsetY -= movement;
        else if (event.key === 'ArrowDown') cropOffsetY += movement;
        else return;

        event.preventDefault();
        renderCrop();
    });

    cropModal.querySelectorAll('[data-profile-crop-cancel]').forEach((button) => {
        button.addEventListener('click', cancelCrop);
    });

    cropApplyButton.addEventListener('click', () => {
        if (!cropImage || cropApplyButton.disabled) {
            return;
        }

        renderCrop();
        cropApplyButton.disabled = true;
        replaceButtonLabel(cropApplyButton, 'Menerapkan...', true);

        cropCanvas.toBlob((blob) => {
            try {
                if (!blob || blob.size > maximumSize) {
                    throw new Error('Hasil crop belum dapat dibuat. Silakan atur ulang foto.');
                }

                const croppedFile = new File([blob], 'foto-profil-crop.jpg', {
                    type: 'image/jpeg',
                    lastModified: Date.now(),
                });
                setInputFile(croppedFile);
                appliedCropFile = croppedFile;
                appliedSourceName = selectedSourceName;
                showAppliedPreview();
                input.dispatchEvent(new Event('input', { bubbles: true }));
                releaseCropSource();
                closeCropModal(saveButton);
            } catch (cropFailure) {
                cropError.textContent = cropFailure instanceof Error
                    ? cropFailure.message
                    : 'Hasil crop belum dapat dibuat. Silakan coba lagi.';
                cropError.hidden = false;
            } finally {
                cropApplyButton.disabled = false;
                replaceButtonLabel(cropApplyButton, 'Terapkan Crop');
            }
        }, 'image/jpeg', 0.9);
    });

    cropModal.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            cancelCrop();
            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const focusable = [...cropModal.querySelectorAll('button:not(:disabled), input:not(:disabled), [tabindex="0"]')];
        const first = focusable[0];
        const last = focusable.at(-1);

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last?.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first?.focus();
        }
    });

    window.addEventListener('pagehide', () => {
        releasePreviewUrl();
        releaseCropSource();
    }, { once: true });
});

const serializeForm = (form) => JSON.stringify(
    [...new FormData(form).entries()]
        .filter(([name]) => !['_token', '_method'].includes(name))
        .map(([name, value]) => [name, value instanceof File
            ? `${value.name}:${value.size}:${value.type}`
            : String(value)])
        .sort(([firstName], [secondName]) => firstName.localeCompare(secondName)),
);

document.querySelectorAll('form[data-dirty-form]').forEach((form) => {
    const initialState = serializeForm(form);
    const updateDirtyState = () => {
        form.dataset.dirty = String(serializeForm(form) !== initialState);
    };

    form.dataset.dirty = 'false';
    form.addEventListener('input', updateDirtyState);
    form.addEventListener('change', updateDirtyState);
});

const hasUnsavedChanges = () => [...document.querySelectorAll('form[data-dirty-form]')]
    .some((form) => form.dataset.dirty === 'true' && form.dataset.submitting !== 'true');

window.addEventListener('beforeunload', (event) => {
    if (hasUnsavedChanges()) {
        event.preventDefault();
        event.returnValue = '';
    }
});

const pageTransition = document.querySelector('[data-page-transition]');
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
let pageNavigationPending = false;

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');

    if (
        !pageTransition
        || !link
        || event.defaultPrevented
        || event.button !== 0
        || event.metaKey
        || event.ctrlKey
        || event.shiftKey
        || event.altKey
        || link.hasAttribute('download')
        || link.hasAttribute('data-no-page-transition')
        || (link.target && link.target !== '_self')
        || hasUnsavedChanges()
    ) {
        return;
    }

    const destination = new URL(link.href, window.location.href);
    const current = new URL(window.location.href);
    const isSameDocumentAnchor = destination.origin === current.origin
        && destination.pathname === current.pathname
        && destination.search === current.search
        && destination.hash !== current.hash;

    if (
        !['http:', 'https:'].includes(destination.protocol)
        || destination.origin !== current.origin
        || destination.href === current.href
        || isSameDocumentAnchor
        || pageNavigationPending
    ) {
        return;
    }

    event.preventDefault();
    pageNavigationPending = true;

    if (reducedMotion.matches) {
        window.location.assign(destination.href);
        return;
    }

    pageTransition.classList.add('is-page-leaving');
    pageTransition.setAttribute('aria-busy', 'true');
    window.setTimeout(() => window.location.assign(destination.href), 180);
});

window.addEventListener('pageshow', () => {
    pageNavigationPending = false;
    pageTransition?.classList.remove('is-page-leaving');
    pageTransition?.removeAttribute('aria-busy');
});

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    if (form.dataset.submitting === 'true') {
        event.preventDefault();
        return;
    }

    const confirmation = confirmationForForm(form, event.submitter);

    if (confirmation && form.dataset.confirmed !== 'true') {
        event.preventDefault();

        if (openConfirmation(form, event.submitter, confirmation)) {
            return;
        }
    }

    delete form.dataset.confirmed;

    if (form.matches('[data-quick-status-form]')) {
        event.preventDefault();
        submitQuickStatus(form);
        return;
    }

    const method = (event.submitter?.getAttribute('formmethod') || form.method || 'get').toLowerCase();
    if (method === 'get') {
        return;
    }

    form.dataset.submitting = 'true';
    form.dataset.dirty = 'false';

    form.querySelectorAll('button[type="submit"]').forEach((button) => {
        button.disabled = true;

        if (button === event.submitter || !event.submitter) {
            replaceButtonLabel(button, button.dataset.loadingLabel || 'Memproses...', true);
        }
    });
});
