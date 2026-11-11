"""
ClubHub - Django Read-Only Models
Phase 10: Unmanaged Django ORM models reflecting the existing MySQL database.
IMPORTANT: All models define `managed = False` so Django will never modify, create,
or drop tables belonging to the PHP application.
"""

from django.db import models


class User(models.Model):
    name = models.CharField(max_length=100)
    email = models.CharField(max_length=100, unique=True)
    password = models.CharField(max_length=255)
    role = models.CharField(max_length=20)
    phone = models.CharField(max_length=20, null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        managed = False
        db_table = 'users'
        verbose_name = 'User'
        verbose_name_plural = 'Users'

    def __str__(self):
        return f"{self.name} ({self.role})"


class Club(models.Model):
    name = models.CharField(max_length=100, unique=True)
    description = models.TextField(null=True, blank=True)
    category = models.CharField(max_length=50)
    head = models.ForeignKey(User, on_delete=models.DO_NOTHING, db_column='head_id', null=True, blank=True, related_name='led_clubs')
    status = models.CharField(max_length=20, default='active')
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        managed = False
        db_table = 'clubs'
        verbose_name = 'Club'
        verbose_name_plural = 'Clubs'

    def __str__(self):
        return self.name


class Venue(models.Model):
    name = models.CharField(max_length=100, unique=True)
    capacity = models.IntegerField(default=50)
    location = models.CharField(max_length=150)
    description = models.TextField(null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        managed = False
        db_table = 'venues'
        verbose_name = 'Venue'
        verbose_name_plural = 'Venues'

    def __str__(self):
        return f"{self.name} ({self.location})"


class Event(models.Model):
    club = models.ForeignKey(Club, on_delete=models.DO_NOTHING, db_column='club_id', related_name='events')
    title = models.CharField(max_length=150)
    description = models.TextField(null=True, blank=True)
    event_date = models.DateField()
    start_time = models.TimeField()
    end_time = models.TimeField()
    venue = models.ForeignKey(Venue, on_delete=models.DO_NOTHING, db_column='venue_id', null=True, blank=True, related_name='events')
    status = models.CharField(max_length=20, default='pending')
    admin_remarks = models.TextField(null=True, blank=True)
    created_by = models.ForeignKey(User, on_delete=models.DO_NOTHING, db_column='created_by', related_name='created_events')
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        managed = False
        db_table = 'events'
        verbose_name = 'Event'
        verbose_name_plural = 'Events'

    def __str__(self):
        return self.title


class Rsvp(models.Model):
    event = models.ForeignKey(Event, on_delete=models.DO_NOTHING, db_column='event_id', related_name='rsvps')
    user = models.ForeignKey(User, on_delete=models.DO_NOTHING, db_column='user_id', related_name='rsvps')
    status = models.CharField(max_length=20, default='attending')
    rsvp_date = models.DateTimeField(auto_now_add=True)

    class Meta:
        managed = False
        db_table = 'rsvps'
        unique_together = (('event', 'user'),)
        verbose_name = 'RSVP'
        verbose_name_plural = 'RSVPs'


class BudgetRequest(models.Model):
    club = models.ForeignKey(Club, on_delete=models.DO_NOTHING, db_column='club_id', related_name='budget_requests')
    title = models.CharField(max_length=150)
    description = models.TextField(null=True, blank=True)
    amount = models.DecimalField(max_digits=10, decimal_places=2)
    status = models.CharField(max_length=20, default='pending')
    admin_remarks = models.TextField(null=True, blank=True)
    created_by = models.ForeignKey(User, on_delete=models.DO_NOTHING, db_column='created_by')
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        managed = False
        db_table = 'budget_requests'
        verbose_name = 'Budget Request'
        verbose_name_plural = 'Budget Requests'


class Expense(models.Model):
    club = models.ForeignKey(Club, on_delete=models.DO_NOTHING, db_column='club_id', related_name='expenses')
    budget_request = models.ForeignKey(BudgetRequest, on_delete=models.DO_NOTHING, db_column='budget_request_id', null=True, blank=True, related_name='expenses')
    title = models.CharField(max_length=150)
    amount = models.DecimalField(max_digits=10, decimal_places=2)
    receipt_file = models.CharField(max_length=255, null=True, blank=True)
    expense_date = models.DateField()
    created_by = models.ForeignKey(User, on_delete=models.DO_NOTHING, db_column='created_by')
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        managed = False
        db_table = 'expenses'
        verbose_name = 'Expense'
        verbose_name_plural = 'Expenses'

