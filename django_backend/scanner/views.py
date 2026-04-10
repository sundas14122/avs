from django.http import JsonResponse
from django.views.decorators.csrf import csrf_exempt
from django.views.decorators.http import require_http_methods
from django.contrib.auth.models import User
from django.utils import timezone
import json
import requests
import socket
import ssl
from datetime import datetime
from .models import Scan
import threading
import time


def check_http_headers(url):
    """Check HTTP security headers"""
    issues = []
    try:
        response = requests.get(url, timeout=10, verify=False)
        headers = response.headers
        
        # Check for security headers
        if 'X-Frame-Options' not in headers:
            issues.append({
                'severity': 'medium',
                'title': 'Missing X-Frame-Options Header',
                'description': 'The X-Frame-Options header is not set, which could enable clickjacking attacks.'
            })
        
        if 'X-Content-Type-Options' not in headers:
            issues.append({
                'severity': 'medium',
                'title': 'Missing X-Content-Type-Options Header',
                'description': 'The X-Content-Type-Options header is not set.'
            })
        
        if 'Strict-Transport-Security' not in headers:
            issues.append({
                'severity': 'high',
                'title': 'Missing HSTS Header',
                'description': 'HTTP Strict Transport Security (HSTS) is not enabled.'
            })
        
        if 'Content-Security-Policy' not in headers:
            issues.append({
                'severity': 'medium',
                'title': 'Missing Content-Security-Policy',
                'description': 'CSP header is not set, increasing XSS risk.'
            })
        
        # Check for information disclosure
        server = headers.get('Server', '')
        if server and 'Apache' in server or 'Nginx' in server:
            issues.append({
                'severity': 'low',
                'title': 'Server Information Disclosure',
                'description': f'Server header reveals: {server}'
            })
            
    except Exception as e:
        issues.append({
            'severity': 'low',
            'title': 'Connection Error',
            'description': f'Could not connect to {url}: {str(e)}'
        })
    
    return issues


def check_ssl_certificate(url):
    """Check SSL/TLS certificate"""
    issues = []
    try:
        if not url.startswith('https'):
            return issues
            
        # Extract host from URL
        host = url.replace('https://', '').split('/')[0]
        
        context = ssl.create_default_context()
        with socket.create_connection((host, 443), timeout=10) as sock:
            with context.wrap_socket(sock, server_hostname=host) as ssock:
                cert = ssock.getpeercert()
                
                # Check certificate expiry
                not_after = datetime.strptime(cert['notAfter'], '%b %d %H:%M:%S %Y %Z')
                days_left = (not_after - datetime.now()).days
                
                if days_left < 30:
                    issues.append({
                        'severity': 'high',
                        'title': 'SSL Certificate Expiring Soon',
                        'description': f'SSL certificate expires in {days_left} days.'
                    })
                
                # Check SSL/TLS version
                if ssock.version() in ['TLSv1', 'TLSv1.1', 'SSLv3']:
                    issues.append({
                        'severity': 'critical',
                        'title': 'Outdated SSL/TLS Version',
                        'description': f'Using outdated protocol: {ssock.version()}'
                    })
                    
    except Exception as e:
        issues.append({
            'severity': 'medium',
            'title': 'SSL Check Error',
            'description': f'Could not check SSL: {str(e)}'
        })
    
    return issues


def check_common_ports(host):
    """Check if common ports are open"""
    issues = []
    common_ports = [21, 22, 23, 25, 53, 80, 110, 143, 443, 445, 3389, 8080, 8443]
    
    for port in common_ports:
        try:
            sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            sock.settimeout(2)
            result = sock.connect_ex((host, port))
            sock.close()
            
            if result == 0:
                if port in [21, 23, 25]:  # FTP, Telnet, SMTP
                    issues.append({
                        'severity': 'medium',
                        'title': f'Port {port} Open',
                        'description': f'Port {port} is open - potential security risk'
                    })
        except:
            pass
    
    return issues


def perform_scan(scan_id, target, scan_type):
    """Perform the actual vulnerability scan"""
    try:
        scan = Scan.objects.get(id=scan_id)
        scan.status = 'running'
        scan.save()
        
        all_issues = []
        
        # Ensure URL has http/https
        if not target.startswith('http'):
            target = 'http://' + target
        
        # Extract host
        host = target.replace('https://', '').replace('http://', '').split('/')[0]
        
        # Run different checks based on scan type
        if scan_type in ['basic', 'full']:
            # Check HTTP headers
            header_issues = check_http_headers(target)
            all_issues.extend(header_issues)
        
        if scan_type == 'full':
            # Check SSL certificate
            if target.startswith('https'):
                ssl_issues = check_ssl_certificate(target)
                all_issues.extend(ssl_issues)
            
            # Check common ports
            port_issues = check_common_ports(host)
            all_issues.extend(port_issues)
        
        # Update scan with results
        scan.results = {
            'vulnerabilities': all_issues,
            'scan_summary': {
                'total_issues': len(all_issues),
                'critical': len([i for i in all_issues if i['severity'] == 'critical']),
                'high': len([i for i in all_issues if i['severity'] == 'high']),
                'medium': len([i for i in all_issues if i['severity'] == 'medium']),
                'low': len([i for i in all_issues if i['severity'] == 'low']),
            },
            'target': target,
            'scan_type': scan_type,
            'scan_date': timezone.now().isoformat()
        }
        scan.vulnerabilities_found = len(all_issues)
        scan.status = 'completed'
        scan.scan_completed = timezone.now()
        scan.save()
        
    except Exception as e:
        scan = Scan.objects.get(id=scan_id)
        scan.status = 'failed'
        scan.results = {'error': str(e)}
        scan.save()


@csrf_exempt
@require_http_methods(["POST"])
def start_scan(request):
    """API endpoint to start a scan"""
    try:
        data = json.loads(request.body)
        
        target = data.get('target')
        scan_type = data.get('scan_type', 'basic')
        user_id = data.get('user_id')
        
        if not target:
            return JsonResponse({'success': False, 'error': 'Target is required'}, status=400)
        
        # Get or create a demo user (since we don't have authentication from PHP)
        user, _ = User.objects.get_or_create(
            username=f'user_{user_id}',
            defaults={'email': f'user{user_id}@example.com'}
        )
        
        # Create scan record
        scan = Scan.objects.create(
            user=user,
            target=target,
            scan_type=scan_type,
            status='pending'
        )
        
        # Start scan in background
        thread = threading.Thread(target=perform_scan, args=(scan.id, target, scan_type))
        thread.start()
        
        return JsonResponse({
            'success': True,
            'message': 'Scan started successfully.',
            'scan_id': scan.id
        })
        
    except Exception as e:
        return JsonResponse({'success': False, 'error': str(e)}, status=500)


@require_http_methods(["GET"])
def get_scan_status(request, scan_id):
    """Get scan results"""
    try:
        scan = Scan.objects.get(id=scan_id)
        return JsonResponse({
            'success': True,
            'scan': {
                'id': scan.id,
                'target': scan.target,
                'scan_type': scan.scan_type,
                'status': scan.status,
                'vulnerabilities_found': scan.vulnerabilities_found,
                'results': scan.results,
                'scan_started': scan.scan_started.isoformat() if scan.scan_started else None,
                'scan_completed': scan.scan_completed.isoformat() if scan.scan_completed else None
            }
        })
    except Scan.DoesNotExist:
        return JsonResponse({'success': False, 'error': 'Scan not found'}, status=404)
    except Exception as e:
        return JsonResponse({'success': False, 'error': str(e)}, status=500)


@csrf_exempt
@require_http_methods(["POST"])
def cancel_scan(request, scan_id):
    """Cancel scan if it is still pending/running."""
    try:
        scan = Scan.objects.get(id=scan_id)
        if scan.status in ['completed', 'failed']:
            return JsonResponse({'success': True, 'message': 'Scan already finished.'})

        scan.status = 'failed'
        existing = scan.results if isinstance(scan.results, dict) else {}
        existing['cancelled'] = True
        existing['cancelled_at'] = timezone.now().isoformat()
        existing['error'] = 'Cancelled by user'
        scan.results = existing
        scan.scan_completed = timezone.now()
        scan.save()

        return JsonResponse({'success': True, 'message': 'Scan cancelled successfully.'})
    except Scan.DoesNotExist:
        return JsonResponse({'success': False, 'error': 'Scan not found'}, status=404)
    except Exception as e:
        return JsonResponse({'success': False, 'error': str(e)}, status=500)
