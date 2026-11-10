/**
 * ClubHub - College Clubs Operations Hub
 * Phase 3: Client-side Interactivity & Form Validation
 * Pure Vanilla JavaScript (No Frameworks)
 */

document.addEventListener('DOMContentLoaded', () => {
    // ----------------------------------------------------
    // 1. Mobile Hamburger Menu Toggle
    // ----------------------------------------------------
    initMobileNav();

    // ----------------------------------------------------
    // 2. Events Page Live Filtering (Search + Category)
    // ----------------------------------------------------
    initEventsFilter();

    // ----------------------------------------------------
    // 3. Form Validation (Login, Register, Contact)
    // ----------------------------------------------------
    initFormValidation();
});

/**
 * Initializes the mobile hamburger menu toggle.
 * Opens and closes the navigation drawer on small screens.
 */
function initMobileNav() {
    const navToggle = document.getElementById('navToggle');
    const navbarMenu = document.getElementById('navbarMenu');

    if (!navToggle || !navbarMenu) return;

    // Toggle menu state on hamburger button click
    navToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = navbarMenu.classList.toggle('is-open');
        navToggle.classList.toggle('is-active', isOpen);
        navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    // Close menu when clicking outside of the header
    document.addEventListener('click', (e) => {
        if (!navToggle.contains(e.target) && !navbarMenu.contains(e.target)) {
            navbarMenu.classList.remove('is-open');
            navToggle.classList.remove('is-active');
            navToggle.setAttribute('aria-expanded', 'false');
        }
    });

    // Close mobile menu automatically if viewport resized to desktop width (> 768px)
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            navbarMenu.classList.remove('is-open');
            navToggle.classList.remove('is-active');
            navToggle.setAttribute('aria-expanded', 'false');
        }
    });
}

/**
 * Live instant filtering for the Events page.
 * Filters event cards by search keyword (title/description) and category dropdown.
 */
function initEventsFilter() {
    const searchInput = document.getElementById('event_search');
    const categorySelect = document.getElementById('event_category') || document.getElementById('event_club');
    const eventCards = document.querySelectorAll('.event-card');
    const filterForm = document.querySelector('.filter-form');

    // Only run if on the events page with event cards present
    if (!eventCards.length || (!searchInput && !categorySelect)) return;

    // Prevent default page reload on form submit
    if (filterForm) {
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            filterEvents();
        });
        filterForm.addEventListener('reset', () => {
            setTimeout(() => {
                filterEvents();
            }, 10);
        });
    }

    // Attach instant filter triggers
    if (searchInput) {
        searchInput.addEventListener('input', filterEvents);
    }
    if (categorySelect) {
        categorySelect.addEventListener('change', filterEvents);
    }

    // Create a container for the "No events found" feedback message if not present
    let noMatchNotice = document.getElementById('noEventsNotice');
    if (!noMatchNotice) {
        const eventsContainer = document.querySelector('.events-grid') || eventCards[0].parentElement;
        noMatchNotice = document.createElement('div');
        noMatchNotice.id = 'noEventsNotice';
        noMatchNotice.className = 'no-events-notice card';
        noMatchNotice.style.display = 'none';
        noMatchNotice.innerHTML = `
            <h3>No Matching Events Found</h3>
            <p>Try searching with a different keyword or selecting "All Categories".</p>
        `;
        eventsContainer.appendChild(noMatchNotice);
    }

    /**
     * Filters event cards based on current search input and category selection.
     */
    function filterEvents() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const selectedCategory = categorySelect ? categorySelect.value.toLowerCase().trim() : '';
        let visibleCount = 0;

        eventCards.forEach((card) => {
            // Get searchable content
            const title = card.querySelector('h3')?.textContent.toLowerCase() || '';
            const desc = card.textContent.toLowerCase();
            const cardCategory = (card.getAttribute('data-category') || '').toLowerCase();

            // Check if matches keyword search
            const matchesQuery = query === '' || title.includes(query) || desc.includes(query);

            // Check if matches category selection
            const matchesCategory = selectedCategory === '' || 
                                    cardCategory === selectedCategory || 
                                    desc.includes(selectedCategory);

            if (matchesQuery && matchesCategory) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // Show/hide "No events found" message
        if (noMatchNotice) {
            noMatchNotice.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }
}

/**
 * Initializes client-side validation on Login, Register, and Contact forms.
 * Displays clean inline error messages underneath invalid fields without alerts.
 */
function initFormValidation() {
    // 1. Login Form Validation
    const loginForm = document.querySelector('form[action="login.php"]');
    if (loginForm) {
        setupFormValidation(loginForm, [
            {
                id: 'email',
                label: 'Email Address',
                rules: ['required', 'email']
            },
            {
                id: 'password',
                label: 'Password',
                rules: ['required', 'minLength:8']
            }
        ]);
    }

    // 2. Register Form Validation
    const registerForm = document.querySelector('form[action="register.php"]');
    if (registerForm) {
        setupFormValidation(registerForm, [
            {
                id: 'fullname',
                label: 'Full Name',
                rules: ['required', 'minLength:3']
            },
            {
                id: 'email',
                label: 'College Email Address',
                rules: ['required', 'email']
            },
            {
                id: 'password',
                label: 'Password',
                rules: ['required', 'minLength:8']
            },
            {
                id: 'confirm_password',
                label: 'Confirm Password',
                rules: ['required', 'match:password']
            },
            {
                id: 'role',
                label: 'Role',
                rules: ['required']
            },
            {
                id: 'department',
                label: 'Academic Department',
                rules: ['required']
            },
            {
                id: 'terms',
                label: 'Terms Agreement',
                rules: ['checkbox']
            }
        ]);
    }

    // 3. Contact Form Validation
    const contactForm = document.querySelector('form[action="contact.php"]');
    if (contactForm) {
        setupFormValidation(contactForm, [
            {
                id: 'contact_name',
                label: 'Full Name',
                rules: ['required', 'minLength:3']
            },
            {
                id: 'contact_email',
                label: 'Email Address',
                rules: ['required', 'email']
            },
            {
                id: 'inquiry_type',
                label: 'Inquiry Category',
                rules: ['required']
            },
            {
                id: 'message',
                label: 'Message',
                rules: ['required', 'minLength:10']
            }
        ]);
    }
}

/**
 * Attaches validation rules and live event listeners to a form.
 *
 * @param {HTMLFormElement} form
 * @param {Array<Object>} fieldConfigs
 */
function setupFormValidation(form, fieldConfigs) {
    // Disable native browser tooltips so custom inline errors show consistently
    form.setAttribute('novalidate', 'true');

    // Attach real-time input / change listeners to clear or show inline errors
    fieldConfigs.forEach((config) => {
        const field = document.getElementById(config.id);
        if (!field) return;

        const eventType = field.type === 'checkbox' || field.tagName === 'SELECT' ? 'change' : 'input';

        field.addEventListener(eventType, () => {
            validateSingleField(field, config, form);
        });

        // Also validate when leaving the field (blur)
        field.addEventListener('blur', () => {
            validateSingleField(field, config, form);
        });
    });

    // Validate all fields on submit
    form.addEventListener('submit', (e) => {
        let isFormValid = true;
        let firstInvalidField = null;

        fieldConfigs.forEach((config) => {
            const field = document.getElementById(config.id);
            if (!field) return;

            const isValid = validateSingleField(field, config, form);
            if (!isValid) {
                isFormValid = false;
                if (!firstInvalidField) {
                    firstInvalidField = field;
                }
            }
        });

        // If validation failed, halt submission and focus the first invalid input
        if (!isFormValid) {
            e.preventDefault();
            if (firstInvalidField) {
                firstInvalidField.focus();
            }
        }
    });
}

/**
 * Validates a single field against its assigned rules and renders inline messages.
 *
 * @param {HTMLElement} field
 * @param {Object} config
 * @param {HTMLFormElement} form
 * @returns {boolean}
 */
function validateSingleField(field, config, form) {
    const value = field.type === 'checkbox' ? field.checked : field.value.trim();
    let errorMessage = '';

    for (const rule of config.rules) {
        // Rule: Required field
        if (rule === 'required') {
            if (field.type === 'checkbox') {
                if (!field.checked) {
                    errorMessage = `${config.label} is required.`;
                    break;
                }
            } else if (!value || value === '') {
                errorMessage = `${config.label} is required.`;
                break;
            }
        }

        // Rule: Checkbox agreement
        if (rule === 'checkbox') {
            if (!field.checked) {
                errorMessage = 'You must agree to the club guidelines to continue.';
                break;
            }
        }

        // Rule: Standard Email format
        if (rule === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                errorMessage = 'Please enter a valid email address (e.g. name@college.edu).';
                break;
            }
        }

        // Rule: Minimum length
        if (rule.startsWith('minLength:') && value) {
            const min = parseInt(rule.split(':')[1], 10);
            if (value.length < min) {
                errorMessage = `${config.label} must be at least ${min} characters long.`;
                break;
            }
        }

        // Rule: Confirm field match (e.g. password confirmation)
        if (rule.startsWith('match:')) {
            const targetId = rule.split(':')[1];
            const targetField = document.getElementById(targetId);
            if (targetField && value !== targetField.value.trim()) {
                errorMessage = 'Passwords do not match. Please re-enter.';
                break;
            }
        }
    }

    if (errorMessage) {
        showInlineError(field, errorMessage);
        return false;
    } else {
        clearInlineError(field);
        return true;
    }
}

/**
 * Injects or updates an inline error message directly below the target form field.
 *
 * @param {HTMLElement} field
 * @param {string} message
 */
function showInlineError(field, message) {
    field.classList.add('input-error');
    field.classList.remove('input-success');
    field.setAttribute('aria-invalid', 'true');

    // Locate or create the error container within the field's parent group
    const parent = field.closest('.form-group') || field.parentElement;
    let errorEl = parent.querySelector('.field-error-message');

    if (!errorEl) {
        errorEl = document.createElement('small');
        errorEl.className = 'field-error-message';
        errorEl.setAttribute('role', 'alert');
        parent.appendChild(errorEl);
    }

    errorEl.textContent = message;
}

/**
 * Removes the inline error message and marks the field as valid.
 *
 * @param {HTMLElement} field
 */
function clearInlineError(field) {
    field.classList.remove('input-error');
    field.classList.add('input-success');
    field.removeAttribute('aria-invalid');

    const parent = field.closest('.form-group') || field.parentElement;
    const errorEl = parent.querySelector('.field-error-message');
    if (errorEl) {
        errorEl.remove();
    }
}

