from django.db import models
from django.contrib.auth.models import User
from django.utils import timezone


class Scan(models.Model):
    """Model for storing scan results"""
    SCAN_TYPES = [
        ('basic', 'Basic Scan'),
        ('full', 'Full Scan'),
        ('quick', 'Quick Scan'),
    ]
    
    STATUS_CHOICES = [
        ('pending', 'Pending'),
        ('running', 'Running'),
        ('completed', 'Completed'),
        ('failed', 'Failed'),
    ]
    
    user = models.ForeignKey(User, on_delete=models.CASCADE)
    target = models.URLField(max_length=500)
    scan_type = models.CharField(max_length=20, choices=SCAN_TYPES, default='basic')
    status = models.CharField(max_length=20, choices=STATUS_CHOICES, default='pending')
    results = models.JSONField(null=True, blank=True)
    vulnerabilities_found = models.IntegerField(default=0)
    scan_started = models.DateTimeField(default=timezone.now)
    scan_completed = models.DateTimeField(null=True, blank=True)
    
    def __str__(self):
        return f"Scan {self.id} - {self.target} ({self.status})"
    
    class Meta:
        ordering = ['-scan_started']
