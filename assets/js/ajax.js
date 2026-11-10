/**
 * ClubHub - AJAX & Asynchronous UI Engine
 * Phase 8: Vanilla JavaScript Fetch API integration with zero external libraries.
 * Features:
 * - Live search for events and clubs with 300 ms debounce
 * - Instant RSVP / Cancel RSVP with live count update without reload
 * - Real-time venue clash & availability check on booking forms
 * - Auto-refreshing notification badge in navbar (every 30 seconds)
 * - Loading indicators and friendly error handling
 */

document.addEventListener('DOMContentLoaded', () => {
    initLiveSearch();
    initRsvpButtons();
    initVenueClashChecker();
    initNotificationBadge();
});

/**
 * Returns a debounced version of the passed function.
 * Delays invoking func until after wait milliseconds have elapsed.
 */
function debounce(func, wait = 300) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

/**
 * Retrieves CSRF token from page metadata or hidden inputs.
 */
function getCSRFToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.content) return meta.content;
    const input = document.querySelector('input[name="csrf_token"]');
    if (input && input.value) return input.value;
    return '';
}

/**
 * Displays a transient floating toast notification.
 */
function showToast(message, type = 'info') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast-item toast-${type}`;
    toast.setAttribute('role', 'alert');
    toast.textContent = message;

    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('toast-fade-out');
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

/**
 * Determines API base path based on whether current URL is in pages/ subfolder.
 */
function getApiBasePath() {
    const path = window.location.pathname.replace(/\\/g, '/');
    return path.includes('/pages/') ? '../api/' : 'api/';
}

// =========================================================================
// 1. LIVE SEARCH FOR EVENTS & CLUBS (300 ms Debounce)
// =========================================================================
function initLiveSearch() {
    const apiBase = getApiBasePath();

    // -------------------------------------------------------------
    // Live Search on Events Page
    // -------------------------------------------------------------
    const eventSearch = document.getElementById('event_search');
    const eventCategory = document.getElementById('event_category');
    const eventsGrid = document.querySelector('.events-grid');

    if (eventsGrid && (eventSearch || eventCategory)) {
        const fetchEvents = debounce(() => {
            const query = eventSearch ? eventSearch.value.trim() : '';
            const cat = eventCategory ? eventCategory.value.trim() : '';

            // Show loading state
            eventsGrid.classList.add('is-loading');
            const originalHeading = eventsGrid.querySelector('h2');
            if (originalHeading) {
                originalHeading.innerHTML = `Scheduled Events <span class="ajax-spinner" aria-hidden="true"></span>`;
            }

            const url = `${apiBase}search_events.php?type=events&q=${encodeURIComponent(query)}&category=${encodeURIComponent(cat)}`;

            fetch(url)
                .then((res) => {
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    return res.json();
                })
                .then((data) => {
                    eventsGrid.classList.remove('is-loading');
                    if (!data.success) {
                        throw new Error(data.error || 'Failed to search events.');
                    }
                    renderEventCards(eventsGrid, data.data, query);
                })
                .catch((err) => {
                    eventsGrid.classList.remove('is-loading');
                    if (originalHeading) originalHeading.textContent = 'Scheduled Events';
                    showLiveSearchError(eventsGrid, 'Unable to load events right now. Please check your network connection.');
                });
        }, 300);

        if (eventSearch) eventSearch.addEventListener('input', fetchEvents);
        if (eventCategory) eventCategory.addEventListener('change', fetchEvents);
    }

    // -------------------------------------------------------------
    // Live Search on Clubs Page
    // -------------------------------------------------------------
    const clubSearch = document.getElementById('search');
    const clubCategory = document.getElementById('category');
    const clubsGrid = document.querySelector('.clubs-grid');

    if (clubsGrid && (clubSearch || clubCategory)) {
        const fetchClubs = debounce(() => {
            const query = clubSearch ? clubSearch.value.trim() : '';
            const cat = clubCategory ? clubCategory.value.trim() : '';

            clubsGrid.classList.add('is-loading');
            const h2 = clubsGrid.querySelector('h2');
            if (h2) {
                h2.innerHTML = `Active Student Clubs <span class="ajax-spinner" aria-hidden="true"></span>`;
            }

            const url = `${apiBase}search_events.php?type=clubs&q=${encodeURIComponent(query)}&category=${encodeURIComponent(cat)}`;

            fetch(url)
                .then((res) => {
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    return res.json();
                })
                .then((data) => {
                    clubsGrid.classList.remove('is-loading');
                    if (!data.success) throw new Error(data.error || 'Failed to search clubs.');
                    renderClubCards(clubsGrid, data.data, query);
                })
                .catch((err) => {
                    clubsGrid.classList.remove('is-loading');
                    if (h2) h2.textContent = 'Active Student Clubs';
                    showLiveSearchError(clubsGrid, 'Unable to load clubs right now. Please check your network connection.');
                });
        }, 300);

        if (clubSearch) clubSearch.addEventListener('input', fetchClubs);
        if (clubCategory) clubCategory.addEventListener('change', fetchClubs);
    }
}

/**
 * Renders dynamically returned event cards into the events grid container.
 */
function renderEventCards(container, events, query) {
    const isPagesSubdir = window.location.pathname.replace(/\\/g, '/').includes('/pages/');
    const prefix = isPagesSubdir ? '' : 'pages/';

    let headingHtml = `<h2>Scheduled Events (${events.length} found)</h2>`;

    if (!events || events.length === 0) {
        container.innerHTML = `
            ${headingHtml}
            <div class="card no-events-notice" style="grid-column: 1 / -1;">
                <h3>No Matching Events Found</h3>
                <p>No scheduled campus events matched "${escapeHtml(query)}". Try clearing your search keyword or selecting "All Categories".</p>
                <p><a href="events.php" class="btn btn-outline">Reset Filter</a></p>
            </div>
        `;
        return;
    }

    let cardsHtml = '';
    events.forEach((ev) => {
        const rsvpBtnText = ev.user_rsvped ? 'Attending ✓ (Cancel)' : 'RSVP / Attend';
        const rsvpBtnClass = ev.user_rsvped ? 'btn-success' : 'btn-outline';

        cardsHtml += `
            <article class="card event-card" data-category="${escapeHtml(ev.club_category.toLowerCase())}" id="event-card-${ev.id}">
                <div class="action-bar" style="margin-bottom: var(--space-xs);">
                    <h3>
                        <a href="${prefix}event-detail.php?id=${ev.id}" style="color: var(--primary); text-decoration: none;">
                            ${escapeHtml(ev.title)}
                        </a>
                    </h3>
                    <span class="badge badge-primary">${escapeHtml(ev.club_category)}</span>
                </div>

                <p><strong>Organized By:</strong> ${escapeHtml(ev.club_name)}</p>

                <p>
                    <strong>Date:</strong>
                    <time datetime="${escapeHtml(ev.event_date)}">${escapeHtml(ev.formatted_date)}</time> |
                    <strong>Time:</strong> ${escapeHtml(ev.formatted_time)}
                </p>

                <p>
                    <strong>Venue:</strong> ${escapeHtml(ev.venue_name)}
                    ${ev.venue_capacity ? `(Capacity: ${ev.venue_capacity})` : ''}
                </p>

                <p>
                    <strong>Status:</strong>
                    <span class="badge badge-${escapeHtml(ev.status)}">${escapeHtml(capitalize(ev.status))}</span>
                </p>

                <p class="rsvp-status-line">
                    <strong>Attendance:</strong>
                    <span class="rsvp-counter" id="rsvp-count-${ev.id}">${ev.rsvp_count}</span> Students Registered
                </p>

                <p>${escapeHtml(ev.description.substring(0, 140))}${ev.description.length > 140 ? '...' : ''}</p>

                <div class="card-actions">
                    <a href="${prefix}event-detail.php?id=${ev.id}" class="btn btn-sm btn-outline">View Details</a>
                    <button type="button" class="btn btn-sm ${rsvpBtnClass} btn-rsvp" data-event-id="${ev.id}">
                        ${rsvpBtnText}
                    </button>
                </div>
            </article>
        `;
    });

    container.innerHTML = headingHtml + cardsHtml;
    // Re-bind interactive RSVP buttons on newly generated elements
    initRsvpButtons();
}

/**
 * Renders dynamically returned club cards into the clubs grid container.
 */
function renderClubCards(container, clubs, query) {
    const isPagesSubdir = window.location.pathname.replace(/\\/g, '/').includes('/pages/');
    const prefix = isPagesSubdir ? '' : 'pages/';

    let headingHtml = `<h2>Active Student Clubs (${clubs.length} found)</h2>`;

    if (!clubs || clubs.length === 0) {
        container.innerHTML = `
            ${headingHtml}
            <div class="card no-events-notice" style="grid-column: 1 / -1;">
                <h3>No Matching Clubs Found</h3>
                <p>No student societies matched "${escapeHtml(query)}". Try selecting "All Categories" or clearing your search text.</p>
                <p><a href="clubs.php" class="btn btn-outline">Reset Directory</a></p>
            </div>
        `;
        return;
    }

    let cardsHtml = '';
    clubs.forEach((club) => {
        cardsHtml += `
            <article class="card club-card" data-category="${escapeHtml(club.category.toLowerCase())}">
                <div class="action-bar" style="margin-bottom: var(--space-xs);">
                    <h3>${escapeHtml(club.name)}</h3>
                    <span class="badge badge-primary">${escapeHtml(club.category)}</span>
                </div>

                <p>
                    <strong>Status:</strong>
                    <span class="badge badge-${club.status === 'active' ? 'success' : 'danger'}">
                        ${escapeHtml(capitalize(club.status))}
                    </span>
                </p>

                <p><strong>Club Head:</strong> ${escapeHtml(club.head_name)}</p>
                <p><strong>Registered Members:</strong> ${club.member_count} Students</p>
                <p>${escapeHtml(club.description)}</p>

                <div class="card-actions">
                    <form action="membership-action.php" method="POST" style="display:inline; margin:0; padding:0; border:none; box-shadow:none; background:transparent;">
                        <input type="hidden" name="csrf_token" value="${escapeHtml(getCSRFToken())}">
                        <input type="hidden" name="action" value="apply">
                        <input type="hidden" name="club_id" value="${club.id}">
                        <button type="submit" class="btn btn-sm btn-primary">+ Apply to Join</button>
                    </form>
                </div>
            </article>
        `;
    });

    container.innerHTML = headingHtml + cardsHtml;
}

function showLiveSearchError(container, message) {
    let errorBox = container.querySelector('.live-search-error');
    if (!errorBox) {
        errorBox = document.createElement('div');
        errorBox.className = 'flash-alert flash-danger live-search-error';
        errorBox.style.gridColumn = '1 / -1';
        container.prepend(errorBox);
    }
    errorBox.textContent = message;
}

// =========================================================================
// 2. RSVP / CANCEL RSVP BUTTON (Asynchronous Update Without Reload)
// =========================================================================
function initRsvpButtons() {
    const apiBase = getApiBasePath();
    const rsvpButtons = document.querySelectorAll('.btn-rsvp:not([data-rsvp-bound])');

    rsvpButtons.forEach((btn) => {
        btn.setAttribute('data-rsvp-bound', 'true');
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const eventId = btn.getAttribute('data-event-id');
            if (!eventId) return;

            const originalText = btn.textContent;
            btn.disabled = true;
            btn.innerHTML = `<span class="ajax-spinner-sm" aria-hidden="true"></span> Saving...`;

            const csrfToken = getCSRFToken();
            const bodyData = new URLSearchParams({
                event_id: eventId,
                csrf_token: csrfToken,
            });

            fetch(`${apiBase}rsvp.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-Token': csrfToken,
                },
                body: bodyData.toString(),
            })
                .then((res) => {
                    if (res.status === 401) {
                        // User not logged in: redirect to login page
                        const isPagesSubdir = window.location.pathname.replace(/\\/g, '/').includes('/pages/');
                        const loginUrl = isPagesSubdir ? 'login.php' : 'pages/login.php';
                        window.location.href = `${loginUrl}?redirect=${encodeURIComponent(window.location.pathname + window.location.search)}`;
                        return null;
                    }
                    if (!res.ok) {
                        return res.json().then((errData) => {
                            throw new Error(errData.error || `HTTP ${res.status}`);
                        });
                    }
                    return res.json();
                })
                .then((data) => {
                    btn.disabled = false;
                    if (!data) return;

                    if (!data.success) {
                        throw new Error(data.error || 'Failed to update RSVP.');
                    }

                    // Update button state and classes
                    if (data.rsvped) {
                        btn.className = 'btn btn-sm btn-success btn-rsvp';
                        btn.textContent = 'Attending ✓ (Cancel)';
                        showToast(data.message || 'RSVP confirmed! See you at the event.', 'success');
                    } else {
                        btn.className = 'btn btn-sm btn-outline btn-rsvp';
                        btn.textContent = 'RSVP / Attend';
                        showToast(data.message || 'RSVP cancelled.', 'info');
                    }

                    // Update live attendee count element
                    const counter = document.getElementById(`rsvp-count-${eventId}`) ||
                                    document.querySelector(`.rsvp-counter[data-event-id="${eventId}"]`);
                    if (counter) {
                        counter.textContent = data.count;
                    }

                    // On event-detail page, also update any detail view badge
                    const detailCounter = document.getElementById('detailRsvpCount');
                    if (detailCounter) {
                        detailCounter.textContent = `${data.count} Registered Attendee${data.count === 1 ? '' : 's'}`;
                    }
                })
                .catch((err) => {
                    btn.disabled = false;
                    btn.textContent = originalText;
                    showToast(err.message || 'Unable to update RSVP. Please try again.', 'danger');
                });
        });
    });
}

// =========================================================================
// 3. INSTANT VENUE CLASH & AVAILABILITY CHECKER (On Event Booking Form)
// =========================================================================
function initVenueClashChecker() {
    const venueSelect = document.getElementById('venue_id');
    const dateInput = document.getElementById('event_date');
    const startInput = document.getElementById('start_time');
    const endInput = document.getElementById('end_time');
    const eventForm = document.querySelector('form[action*="event-form.php"]');

    if (!venueSelect || !dateInput || !startInput || !endInput) return;

    // Create feedback container right below the venue selector if not present
    let feedbackEl = document.getElementById('venueClashFeedback');
    if (!feedbackEl) {
        feedbackEl = document.createElement('div');
        feedbackEl.id = 'venueClashFeedback';
        feedbackEl.className = 'venue-feedback';
        feedbackEl.style.display = 'none';
        venueSelect.parentElement.appendChild(feedbackEl);
    }

    const checkAvailability = debounce(() => {
        const venueId = venueSelect.value;
        const dateVal = dateInput.value;
        const startTime = startInput.value;
        const endTime = endInput.value;
        const eventIdInput = document.querySelector('input[name="id"]');
        const eventId = eventIdInput ? eventIdInput.value : '0';

        // Clear if venue is not selected
        if (!venueId || venueId === '') {
            feedbackEl.style.display = 'none';
            venueSelect.classList.remove('input-error', 'input-success');
            return;
        }

        // Only check when all 4 fields are filled
        if (!dateVal || !startTime || !endTime) {
            feedbackEl.style.display = 'none';
            venueSelect.classList.remove('input-error', 'input-success');
            return;
        }

        // Basic chronological check
        if (endTime <= startTime) {
            feedbackEl.style.display = 'block';
            feedbackEl.className = 'venue-feedback venue-error';
            feedbackEl.textContent = '⚠️ End time must be later than start time.';
            venueSelect.classList.remove('input-success');
            venueSelect.classList.add('input-error');
            return;
        }

        // Show checking state
        feedbackEl.style.display = 'block';
        feedbackEl.className = 'venue-feedback venue-checking';
        feedbackEl.innerHTML = `<span class="ajax-spinner-sm" aria-hidden="true"></span> Checking venue availability...`;

        const apiBase = getApiBasePath();
        const url = `${apiBase}check_venue.php?venue_id=${encodeURIComponent(venueId)}&event_date=${encodeURIComponent(dateVal)}&start_time=${encodeURIComponent(startTime)}&end_time=${encodeURIComponent(endTime)}&event_id=${encodeURIComponent(eventId)}`;

        fetch(url)
            .then((res) => {
                if (res.status === 409) {
                    // Conflict found
                    return res.json().then((data) => ({ status: 409, data }));
                }
                if (!res.ok) {
                    return res.json().then((data) => {
                        throw new Error(data.error || `HTTP ${res.status}`);
                    });
                }
                return res.json().then((data) => ({ status: 200, data }));
            })
            .then((result) => {
                const { status, data } = result;

                if (status === 409 || !data.available) {
                    // Clash detected!
                    feedbackEl.className = 'venue-feedback venue-error';
                    feedbackEl.innerHTML = `<strong>⚠️ Booking Conflict:</strong> ${escapeHtml(data.message)}`;
                    venueSelect.classList.remove('input-success');
                    venueSelect.classList.add('input-error');

                    const submitBtn = eventForm ? eventForm.querySelector('button[type="submit"]') : null;
                    if (submitBtn) submitBtn.setAttribute('title', 'Resolve venue booking conflict first');
                } else {
                    // Available!
                    feedbackEl.className = 'venue-feedback venue-success';
                    feedbackEl.innerHTML = `<strong>✓ Available:</strong> ${escapeHtml(data.message)}`;
                    venueSelect.classList.remove('input-error');
                    venueSelect.classList.add('input-success');

                    const submitBtn = eventForm ? eventForm.querySelector('button[type="submit"]') : null;
                    if (submitBtn) submitBtn.removeAttribute('title');
                }
            })
            .catch((err) => {
                feedbackEl.className = 'venue-feedback venue-warning';
                feedbackEl.textContent = 'Could not verify venue availability in real-time. Full validation will run upon saving.';
                venueSelect.classList.remove('input-error', 'input-success');
            });
    }, 300);

    venueSelect.addEventListener('change', checkAvailability);
    dateInput.addEventListener('change', checkAvailability);
    startInput.addEventListener('change', checkAvailability);
    endInput.addEventListener('change', checkAvailability);
}

// =========================================================================
// 4. NOTIFICATION BADGE IN NAVBAR (Refreshes Every 30 Seconds)
// =========================================================================
function initNotificationBadge() {
    const dashboardLink = document.querySelector('.navbar-nav a[href*="dashboard"]');
    if (!dashboardLink) return; // Only runs when user is logged in with Dashboard link present

    let badge = document.getElementById('navbarNotificationBadge');
    if (!badge) {
        badge = document.createElement('span');
        badge.id = 'navbarNotificationBadge';
        badge.className = 'nav-badge-count';
        badge.style.display = 'none';
        dashboardLink.appendChild(badge);
    }

    const apiBase = getApiBasePath();

    function fetchNotifications() {
        fetch(`${apiBase}notifications.php`)
            .then((res) => {
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.json();
            })
            .then((data) => {
                if (!data || !data.authenticated) {
                    badge.style.display = 'none';
                    return;
                }

                const count = parseInt(data.count, 10) || 0;
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.style.display = 'inline-flex';
                    badge.setAttribute('title', `${count} pending operations requiring your attention`);
                } else {
                    badge.style.display = 'none';
                }
            })
            .catch((err) => {
                // Fail silently without interrupting UI
                console.warn('Notification auto-refresh failed:', err.message);
            });
    }

    // Initial fetch on page load
    fetchNotifications();

    // Refreshes count every 30 seconds (30000 ms)
    setInterval(fetchNotifications, 30000);
}

// =========================================================================
// HELPER FUNCTIONS
// =========================================================================
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

