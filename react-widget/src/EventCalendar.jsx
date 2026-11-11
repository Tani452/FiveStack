import React, { useState, useEffect, useMemo } from 'react';
import './calendar.css';

/**
 * Returns dynamic API endpoint path based on browser location.
 */
function getApiUrl() {
  if (typeof window === 'undefined') return 'api/events.php';
  if (window.location.hostname === 'localhost' && window.location.port === '5173') {
    return 'http://localhost/clubhub/api/events.php';
  }
  const path = window.location.pathname.replace(/\\/g, '/');
  return path.includes('/pages/') ? '../api/events.php' : 'api/events.php';
}

/**
 * Returns event detail page link based on current path depth.
 */
function getDetailUrl(id) {
  if (typeof window === 'undefined') return `event-detail.php?id=${id}`;
  const path = window.location.pathname.replace(/\\/g, '/');
  return path.includes('/pages/') ? `event-detail.php?id=${id}` : `pages/event-detail.php?id=${id}`;
}

const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

export default function EventCalendar() {
  const [events, setEvents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Default calendar month: Use November 2026 if events exist there, or current date
  const [currentDate, setCurrentDate] = useState(() => new Date(2026, 10, 1)); // Nov 2026 (0-indexed month 10)
  const [selectedDateStr, setSelectedDateStr] = useState('2026-11-15');
  const [selectedCategory, setSelectedCategory] = useState('All');

  // Fetch approved events from api/events.php
  const fetchEvents = () => {
    setLoading(true);
    setError(null);

    const apiUrl = getApiUrl();
    fetch(apiUrl)
      .then((res) => {
        if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
        return res.json();
      })
      .then((data) => {
        if (!data || !data.success) {
          throw new Error(data?.error || 'Failed to retrieve events');
        }
        setEvents(data.data || []);
        setLoading(false);

        // If events exist, default selected date to first event
        if (data.data && data.data.length > 0) {
          const firstEv = data.data[0];
          setSelectedDateStr(firstEv.event_date);
          const [y, m, d] = firstEv.event_date.split('-').map(Number);
          setCurrentDate(new Date(y, m - 1, 1));
        }
      })
      .catch((err) => {
        console.error('EventCalendar fetch error:', err);
        setError(err.message || 'Unable to load campus events schedule');
        setLoading(false);
      });
  };

  useEffect(() => {
    fetchEvents();
  }, []);

  // Compute available categories from events
  const categories = useMemo(() => {
    const set = new Set();
    events.forEach((ev) => {
      if (ev.club_category) set.add(ev.club_category);
    });
    return ['All', ...Array.from(set)];
  }, [events]);

  // Filter events by selected category
  const filteredEvents = useMemo(() => {
    if (selectedCategory === 'All') return events;
    return events.filter(
      (ev) => (ev.club_category || '').toLowerCase() === selectedCategory.toLowerCase()
    );
  }, [events, selectedCategory]);

  // Group filtered events by date ('YYYY-MM-DD')
  const eventsByDate = useMemo(() => {
    const map = {};
    filteredEvents.forEach((ev) => {
      const dateKey = ev.event_date;
      if (!map[dateKey]) map[dateKey] = [];
      map[dateKey].push(ev);
    });
    return map;
  }, [filteredEvents]);

  // Calendar calculations
  const year = currentDate.getFullYear();
  const month = currentDate.getMonth(); // 0 to 11

  const monthName = currentDate.toLocaleString('default', { month: 'long' });

  // First day of current month (0 = Sun, 1 = Mon, etc.)
  const firstDayIndex = new Date(year, month, 1).getDay();

  // Days in current month
  const daysInCurrentMonth = new Date(year, month + 1, 0).getDate();

  // Days in previous month
  const daysInPrevMonth = new Date(year, month, 0).getDate();

  // Navigation handlers
  const handlePrevMonth = () => {
    setCurrentDate(new Date(year, month - 1, 1));
  };

  const handleNextMonth = () => {
    setCurrentDate(new Date(year, month + 1, 1));
  };

  const handleToday = () => {
    const today = new Date();
    setCurrentDate(new Date(today.getFullYear(), today.getMonth(), 1));
    const pad = (n) => String(n).padStart(2, '0');
    setSelectedDateStr(`${today.getFullYear()}-${pad(today.getMonth() + 1)}-${pad(today.getDate())}`);
  };

  // Helper to format date key YYYY-MM-DD
  const formatDateKey = (y, m, d) => {
    const pad = (n) => String(n).padStart(2, '0');
    return `${y}-${pad(m + 1)}-${pad(d)}`;
  };

  // Today string for comparison
  const todayObj = new Date();
  const todayStr = formatDateKey(todayObj.getFullYear(), todayObj.getMonth(), todayObj.getDate());

  // Generate day cells
  const dayCells = [];

  // 1. Previous month trailing padding days
  for (let i = firstDayIndex - 1; i >= 0; i--) {
    const dayNum = daysInPrevMonth - i;
    const prevMonthDate = new Date(year, month - 1, dayNum);
    const dateKey = formatDateKey(prevMonthDate.getFullYear(), prevMonthDate.getMonth(), dayNum);
    dayCells.push({
      dayNum,
      dateKey,
      isCurrentMonth: false,
      events: eventsByDate[dateKey] || [],
    });
  }

  // 2. Current month days
  for (let d = 1; d <= daysInCurrentMonth; d++) {
    const dateKey = formatDateKey(year, month, d);
    dayCells.push({
      dayNum: d,
      dateKey,
      isCurrentMonth: true,
      events: eventsByDate[dateKey] || [],
    });
  }

  // 3. Next month leading padding days to make grid complete 35 or 42 cells
  const remainingCells = 42 - dayCells.length;
  const nextMonthPadding = remainingCells >= 7 ? remainingCells - 7 : remainingCells;
  for (let d = 1; d <= nextMonthPadding; d++) {
    const nextMonthDate = new Date(year, month + 1, d);
    const dateKey = formatDateKey(nextMonthDate.getFullYear(), nextMonthDate.getMonth(), d);
    dayCells.push({
      dayNum: d,
      dateKey,
      isCurrentMonth: false,
      events: eventsByDate[dateKey] || [],
    });
  }

  // Events on the currently selected date
  const selectedDayEvents = eventsByDate[selectedDateStr] || [];

  // Format selected date nicely for the panel header
  const formattedSelectedDate = useMemo(() => {
    if (!selectedDateStr) return '';
    const parts = selectedDateStr.split('-');
    if (parts.length !== 3) return selectedDateStr;
    const d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
    return d.toLocaleDateString('default', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });
  }, [selectedDateStr]);

  return (
    <section className="clubhub-calendar-widget" aria-label="Interactive Campus Event Calendar">
      {/* Header with Month Navigation */}
      <div className="calendar-widget-header">
        <div className="calendar-title-group">
          <h2>
            <span>📅</span> Campus Event Calendar
          </h2>
          <p>Interactive schedule view powered by React &amp; live API feed</p>
        </div>

        <div className="calendar-controls">
          <button
            type="button"
            className="calendar-nav-btn"
            onClick={handlePrevMonth}
            aria-label="Previous month"
            title="Previous month"
          >
            &larr; Prev
          </button>
          <span className="current-month-display" aria-live="polite">
            {monthName} {year}
          </span>
          <button
            type="button"
            className="calendar-nav-btn"
            onClick={handleNextMonth}
            aria-label="Next month"
            title="Next month"
          >
            Next &rarr;
          </button>
          <button
            type="button"
            className="calendar-nav-btn today-btn"
            onClick={handleToday}
            title="Jump to current date"
          >
            Today
          </button>
        </div>
      </div>

      {/* Category Filter Pills */}
      {categories.length > 2 && (
        <div className="calendar-filter-bar">
          <span className="filter-label">Filter Category:</span>
          {categories.map((cat) => (
            <button
              key={cat}
              type="button"
              className={`cat-pill ${selectedCategory === cat ? 'active' : ''}`}
              onClick={() => setSelectedCategory(cat)}
            >
              {cat}
            </button>
          ))}
        </div>
      )}

      {/* Loading & Error States */}
      {loading && (
        <div className="calendar-loading-box">
          <div className="calendar-spinner" aria-hidden="true"></div>
          <p>Loading campus events calendar...</p>
        </div>
      )}

      {error && (
        <div className="calendar-error-box" role="alert">
          <p>
            <strong>Error loading events:</strong> {error}
          </p>
          <button type="button" className="calendar-retry-btn" onClick={fetchEvents}>
            ↻ Retry
          </button>
        </div>
      )}

      {!loading && !error && (
        <div className="calendar-layout">
          {/* Calendar Month Grid */}
          <div className="calendar-grid-wrapper">
            <div className="month-grid" role="grid" aria-label={`${monthName} ${year}`}>
              {/* Day of Week Headers */}
              {WEEKDAYS.map((wd) => (
                <div key={wd} className="weekday-header" role="columnheader">
                  {wd}
                </div>
              ))}

              {/* Day Cells */}
              {dayCells.map((cell, idx) => {
                const isToday = cell.dateKey === todayStr;
                const isSelected = cell.dateKey === selectedDateStr;
                const hasEvents = cell.events.length > 0;

                return (
                  <div
                    key={`${cell.dateKey}-${idx}`}
                    className={`calendar-day-cell ${cell.isCurrentMonth ? '' : 'other-month'} ${
                      isToday ? 'is-today' : ''
                    } ${isSelected ? 'is-selected' : ''}`}
                    onClick={() => setSelectedDateStr(cell.dateKey)}
                    role="gridcell"
                    tabIndex={0}
                    onKeyDown={(e) => {
                      if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        setSelectedDateStr(cell.dateKey);
                      }
                    }}
                    aria-selected={isSelected}
                    aria-label={`${cell.dateKey}${hasEvents ? `, ${cell.events.length} event(s)` : ''}`}
                  >
                    <div className="day-cell-top">
                      <span className="day-number">{cell.dayNum}</span>
                    </div>

                    <div className="day-cell-events">
                      {hasEvents && (
                        <>
                          <div className="event-dot-container">
                            {cell.events.slice(0, 3).map((ev) => {
                              const cat = (ev.club_category || '').toLowerCase();
                              let dotClass = 'event-dot';
                              if (cat.includes('tech')) dotClass += ' dot-tech';
                              else if (cat.includes('cult')) dotClass += ' dot-cult';
                              else if (cat.includes('lit')) dotClass += ' dot-lit';
                              return <span key={ev.id} className={dotClass} title={ev.title} />;
                            })}
                          </div>
                          <span className="event-count-badge">
                            {cell.events.length} {cell.events.length === 1 ? 'event' : 'events'}
                          </span>
                        </>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          </div>

          {/* Selected Day Events List Panel */}
          <aside className="day-details-panel" aria-live="polite">
            <div className="panel-header">
              <h3>Events on this Day</h3>
              <p>{formattedSelectedDate || selectedDateStr}</p>
            </div>

            {selectedDayEvents.length === 0 ? (
              <div className="no-events-for-day">
                <span className="calendar-empty-icon" aria-hidden="true">
                  ☕
                </span>
                <p>
                  <strong>No events scheduled.</strong>
                </p>
                <p>Click on any date with a colored badge to inspect scheduled activities.</p>
              </div>
            ) : (
              <div className="panel-events-list">
                {selectedDayEvents.map((ev) => (
                  <article key={ev.id} className="panel-event-card">
                    <div className="panel-event-time">
                      <span>⏰</span>
                      <span>{ev.formatted_time || ev.start_time}</span>
                    </div>

                    <h4 className="panel-event-title">{ev.title}</h4>

                    <div className="panel-event-badges">
                      <span className="mini-badge mini-badge-category">{ev.club_category}</span>
                      <span className="mini-badge mini-badge-attendees">
                        👥 {ev.rsvp_count} RSVP{ev.rsvp_count === 1 ? '' : 's'}
                      </span>
                    </div>

                    <div className="panel-event-meta">
                      <div>
                        <strong>Club:</strong> {ev.club_name}
                      </div>
                      <div>
                        <strong>Venue:</strong> {ev.venue_name}
                        {ev.venue_location ? ` (${ev.venue_location})` : ''}
                      </div>
                    </div>

                    <a href={getDetailUrl(ev.id)} className="view-event-btn">
                      View Event &rarr;
                    </a>
                  </article>
                ))}
              </div>
            )}
          </aside>
        </div>
      )}
    </section>
  );
}

