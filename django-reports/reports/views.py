"""
ClubHub - Django Reports & Analytics Views
Phase 10: Aggregates club performance, event attendances, and financial budgets.
"""

import csv
import json
from decimal import Decimal
from django.shortcuts import render
from django.http import HttpResponse
from django.db.models import Count, Sum, Q
from .models import Club, Event, Rsvp, BudgetRequest, Expense, User


def dashboard(request):
    """
    Renders the Administrative Reports & Analytics Dashboard.
    Visualizes events per club, RSVPs per event, and budget financials.
    """
    # ------------------------------------------------------------------
    # 1. SUMMARY KPIS
    # ------------------------------------------------------------------
    total_clubs = Club.objects.count()
    total_events = Event.objects.count()
    total_rsvps = Rsvp.objects.filter(status='attending').count()

    total_budget_approved = BudgetRequest.objects.filter(status='approved').aggregate(
        total=Sum('amount')
    )['total'] or Decimal('0.00')

    total_expenses_spent = Expense.objects.aggregate(
        total=Sum('amount')
    )['total'] or Decimal('0.00')

    total_budget_remaining = max(Decimal('0.00'), total_budget_approved - total_expenses_spent)

    # ------------------------------------------------------------------
    # 2. EVENTS PER CLUB
    # ------------------------------------------------------------------
    clubs = Club.objects.all().order_by('name')
    events_per_club_list = []
    chart_club_labels = []
    chart_club_event_counts = []
    chart_club_approved_counts = []

    for club in clubs:
        total_ev = Event.objects.filter(club=club).count()
        approved_ev = Event.objects.filter(club=club, status='approved').count()
        pending_ev = Event.objects.filter(club=club, status='pending').count()

        events_per_club_list.append({
            'club': club,
            'total_events': total_ev,
            'approved_events': approved_ev,
            'pending_events': pending_ev,
        })

        chart_club_labels.append(club.name)
        chart_club_event_counts.append(total_ev)
        chart_club_approved_counts.append(approved_ev)

    # ------------------------------------------------------------------
    # 3. RSVPS PER EVENT
    # ------------------------------------------------------------------
    events = Event.objects.select_related('club', 'venue').all().order_by('event_date')
    rsvps_per_event_list = []
    chart_event_labels = []
    chart_event_rsvp_counts = []

    for ev in events:
        attending_count = Rsvp.objects.filter(event=ev, status='attending').count()
        interested_count = Rsvp.objects.filter(event=ev, status='interested').count()

        rsvps_per_event_list.append({
            'event': ev,
            'attending_count': attending_count,
            'interested_count': interested_count,
            'total_rsvps': attending_count + interested_count,
        })

        # Truncate label for cleaner chart display
        short_title = ev.title if len(ev.title) <= 24 else ev.title[:22] + '..'
        chart_event_labels.append(short_title)
        chart_event_rsvp_counts.append(attending_count)

    # ------------------------------------------------------------------
    # 4. BUDGET: REQUESTED VS APPROVED VS SPENT PER CLUB
    # ------------------------------------------------------------------
    budget_breakdown_list = []
    chart_budget_labels = []
    chart_budget_requested = []
    chart_budget_approved = []
    chart_budget_spent = []

    for club in clubs:
        req_sum = BudgetRequest.objects.filter(club=club).aggregate(s=Sum('amount'))['s'] or Decimal('0.00')
        app_sum = BudgetRequest.objects.filter(club=club, status='approved').aggregate(s=Sum('amount'))['s'] or Decimal('0.00')
        spent_sum = Expense.objects.filter(club=club).aggregate(s=Sum('amount'))['s'] or Decimal('0.00')
        remaining = app_sum - spent_sum

        budget_breakdown_list.append({
            'club': club,
            'requested': req_sum,
            'approved': app_sum,
            'spent': spent_sum,
            'remaining': remaining,
            'utilization_pct': round((spent_sum / app_sum * 100), 1) if app_sum > 0 else 0,
        })

        chart_budget_labels.append(club.name)
        chart_budget_requested.append(float(req_sum))
        chart_budget_approved.append(float(app_sum))
        chart_budget_spent.append(float(spent_sum))

    context = {
        'total_clubs': total_clubs,
        'total_events': total_events,
        'total_rsvps': total_rsvps,
        'total_budget_approved': total_budget_approved,
        'total_expenses_spent': total_expenses_spent,
        'total_budget_remaining': total_budget_remaining,

        'events_per_club_list': events_per_club_list,
        'rsvps_per_event_list': rsvps_per_event_list,
        'budget_breakdown_list': budget_breakdown_list,

        # JSON-encoded chart datasets
        'chart_club_labels': json.dumps(chart_club_labels),
        'chart_club_event_counts': json.dumps(chart_club_event_counts),
        'chart_club_approved_counts': json.dumps(chart_club_approved_counts),

        'chart_event_labels': json.dumps(chart_event_labels),
        'chart_event_rsvp_counts': json.dumps(chart_event_rsvp_counts),

        'chart_budget_labels': json.dumps(chart_budget_labels),
        'chart_budget_requested': json.dumps(chart_budget_requested),
        'chart_budget_approved': json.dumps(chart_budget_approved),
        'chart_budget_spent': json.dumps(chart_budget_spent),
    }

    return render(request, 'reports/dashboard.html', context)


def export_csv(request):
    """
    Exports a consolidated CSV report of ClubHub operational and budget statistics.
    """
    response = HttpResponse(content_type='text/csv')
    response['Content-Disposition'] = 'attachment; filename="clubhub_reports_export.csv"'

    writer = csv.writer(response)
    writer.writerow(['ClubHub Administration - Consolidated Analytical Report'])
    writer.writerow([])

    # 1. Budget by Club
    writer.writerow(['--- BUDGET REQUESTED VS APPROVED VS SPENT PER CLUB ---'])
    writer.writerow(['Club Name', 'Category', 'Total Requested ($)', 'Total Approved ($)', 'Total Spent ($)', 'Remaining ($)', 'Utilization %'])
    clubs = Club.objects.all().order_by('name')
    for club in clubs:
        req = BudgetRequest.objects.filter(club=club).aggregate(s=Sum('amount'))['s'] or Decimal('0.00')
        app = BudgetRequest.objects.filter(club=club, status='approved').aggregate(s=Sum('amount'))['s'] or Decimal('0.00')
        spent = Expense.objects.filter(club=club).aggregate(s=Sum('amount'))['s'] or Decimal('0.00')
        rem = app - spent
        pct = round((spent / app * 100), 1) if app > 0 else 0
        writer.writerow([club.name, club.category, f"{req:.2f}", f"{app:.2f}", f"{spent:.2f}", f"{rem:.2f}", f"{pct}%"])

    writer.writerow([])

    # 2. Events per Club
    writer.writerow(['--- EVENTS PER CLUB ---'])
    writer.writerow(['Club Name', 'Total Events Scheduled', 'Approved Events', 'Pending Events'])
    for club in clubs:
        tot = Event.objects.filter(club=club).count()
        app_e = Event.objects.filter(club=club, status='approved').count()
        pen_e = Event.objects.filter(club=club, status='pending').count()
        writer.writerow([club.name, tot, app_e, pen_e])

    writer.writerow([])

    # 3. RSVPs per Event
    writer.writerow(['--- RSVPS PER EVENT ---'])
    writer.writerow(['Event Title', 'Organizing Club', 'Event Date', 'Attending RSVPs', 'Interested RSVPs', 'Status'])
    events = Event.objects.select_related('club').all().order_by('event_date')
    for ev in events:
        att = Rsvp.objects.filter(event=ev, status='attending').count()
        inte = Rsvp.objects.filter(event=ev, status='interested').count()
        writer.writerow([ev.title, ev.club.name, ev.event_date.strftime('%Y-%m-%d'), att, inte, ev.status])

    return response

