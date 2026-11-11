"""
WSGI config for clubhub_reports project.
"""
import os
from django.core.wsgi import get_wsgi_application

os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'clubhub_reports.settings')
application = get_wsgi_application()

