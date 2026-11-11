import React from 'react';
import ReactDOM from 'react-dom/client';
import EventCalendar from './EventCalendar';

function mountCalendar() {
  const rootElement = document.getElementById('calendar-root');
  if (rootElement) {
    ReactDOM.createRoot(rootElement).render(
      <React.StrictMode>
        <EventCalendar />
      </React.StrictMode>
    );
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mountCalendar);
} else {
  mountCalendar();
}

