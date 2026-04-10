from django.urls import path
from . import views

urlpatterns = [
    path('scan/start/', views.start_scan, name='start_scan'),
    path('scan/status/<int:scan_id>/', views.get_scan_status, name='get_scan_status'),
    path('scan/cancel/<int:scan_id>/', views.cancel_scan, name='cancel_scan'),
]
