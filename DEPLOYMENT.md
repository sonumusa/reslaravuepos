# ResLaraVuePOS Deployment Checklist

## Pre-Deployment

### Backend (Laravel)
- [ ] Update .env with production database credentials
- [ ] Set APP_ENV=production
- [ ] Set APP_DEBUG=false 
- [ ] Generate new APP_KEY: php artisan key:generate
- [ ] Configure production PRA credentials
- [ ] Set up Redis for queue (if using)
- [ ] Configure email service (for notifications)
- [ ] Set up scheduled tasks (cron)
- [ ] Run migrations: php artisan migrate --force
- [ ] Run seeders (only initial data, not test data)
- [ ] Set file permissions: storage/ and bootstrap/cache/
- [ ] Configure HTTPS/SSL
- [ ] Set up backup strategy

### Frontend (Vue)
- [ ] Update .env with production API URL
- [ ] Set production base URL in vite.config.js
- [ ] Build for production: npm run build
- [ ] Upload dist/ folder to web server
- [ ] Configure web server (Nginx/Apache) for SPA routing
- [ ] Enable HTTPS
- [ ] Set up CDN (optional)

### Server Requirements
- [ ] PHP 8.1+
- [ ] MySQL 8.0+
- [ ] Composer installed
- [ ] Node.js 18+ (for building)
- [ ] SSL certificate
- [ ] Sufficient storage for uploads
- [ ] Cron job for Laravel scheduler

### Queue Worker (if using)
- [ ] Install supervisor
- [ ] Configure supervisor for queue:work
- [ ] Start queue worker: supervisorctl start reslaravuepos-worker

### Post-Deployment
- [ ] Test all critical user flows
- [ ] Verify PRA integration in production
- [ ] Check receipt printing on actual hardware
- [ ] Train staff on system usage
- [ ] Set up monitoring (Laravel Telescope, Sentry)
- [ ] Configure backups (daily database dumps)
- [ ] Document admin procedures
