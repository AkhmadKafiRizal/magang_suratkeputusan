const dashboardShell = document.querySelector('[data-dashboard-shell]');

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

const dismissToast = (toast) => {
    if (!toast || toast.classList.contains('is-leaving')) {
        return;
    }

    const container = toast.closest('[data-toast-container]') || toast;

    toast.classList.add('is-leaving');
    container.classList.add('is-leaving');
    window.setTimeout(() => container.remove(), 240);
};

document.querySelectorAll('[data-toast]').forEach((toast) => {
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
});

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

    const title = confirmationModal.querySelector('[data-confirmation-title]');
    const message = confirmationModal.querySelector('[data-confirmation-message]');
    const cancelButton = confirmationModal.querySelector('[data-confirmation-cancel]');
    const submitButton = confirmationModal.querySelector('[data-confirmation-submit]');

    title.textContent = config.title;
    message.textContent = config.message;
    replaceButtonLabel(cancelButton, config.cancelLabel || 'Batal');
    replaceButtonLabel(submitButton, config.confirmLabel || 'Ya, Lanjutkan');
    submitButton.disabled = false;
    confirmationModal.hidden = false;
    document.body.classList.add('modal-is-open');
    window.requestAnimationFrame(() => {
        confirmationModal.classList.add('is-open');
        submitButton.focus();
    });

    return true;
};

const confirmationForForm = (form) => {
    if (form.dataset.confirmTitle) {
        return {
            title: form.dataset.confirmTitle,
            message: form.dataset.confirmMessage,
            confirmLabel: form.dataset.confirmLabel,
            cancelLabel: form.dataset.confirmCancelLabel,
            loadingLabel: form.dataset.confirmLoadingLabel,
        };
    }

    if (!form.hasAttribute('data-assignment-confirm')) {
        return null;
    }

    const employeeSelect = form.querySelector('[name="pegawai_id"]');
    const initialEmployeeId = form.dataset.initialPegawaiId || '';
    const currentEmployeeId = employeeSelect?.value || '';

    if (!initialEmployeeId || initialEmployeeId === currentEmployeeId) {
        return null;
    }

    const initialEmployeeName = form.dataset.initialPegawaiName || 'pegawai sebelumnya';

    if (!currentEmployeeId) {
        return {
            title: 'Hapus Penugasan?',
            message: `${initialEmployeeName} tidak lagi menjadi pegawai yang menangani surat ini.`,
            confirmLabel: 'Ya, Hapus Penugasan',
            loadingLabel: 'Menghapus...',
        };
    }

    const selectedOption = employeeSelect?.selectedOptions?.[0];
    const newEmployeeName = selectedOption?.dataset.pegawaiName || 'pegawai yang baru';

    return {
        title: 'Ganti Pegawai Penanganan?',
        message: `Surat ini akan dialihkan dari ${initialEmployeeName} ke ${newEmployeeName}.`,
        confirmLabel: 'Ya, Ganti Pegawai',
        loadingLabel: 'Memindahkan...',
    };
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
        confirmationForm.requestSubmit(confirmationSubmitter || undefined);
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

const serializeForm = (form) => JSON.stringify(
    [...new FormData(form).entries()]
        .filter(([name]) => !['_token', '_method'].includes(name))
        .map(([name, value]) => [name, String(value)])
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

window.addEventListener('beforeunload', (event) => {
    const hasUnsavedChanges = [...document.querySelectorAll('form[data-dirty-form]')]
        .some((form) => form.dataset.dirty === 'true' && form.dataset.submitting !== 'true');

    if (hasUnsavedChanges) {
        event.preventDefault();
        event.returnValue = '';
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() === 'get') {
        return;
    }

    if (form.dataset.submitting === 'true') {
        event.preventDefault();
        return;
    }

    const confirmation = confirmationForForm(form);

    if (confirmation && form.dataset.confirmed !== 'true') {
        event.preventDefault();

        if (openConfirmation(form, event.submitter, confirmation)) {
            return;
        }
    }

    delete form.dataset.confirmed;
    form.dataset.submitting = 'true';
    form.dataset.dirty = 'false';

    form.querySelectorAll('button[type="submit"]').forEach((button) => {
        button.disabled = true;

        if (button === event.submitter || !event.submitter) {
            replaceButtonLabel(button, button.dataset.loadingLabel || 'Memproses...', true);
        }
    });
});
