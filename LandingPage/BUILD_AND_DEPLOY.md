# 🚀 PointWave Landing Page - Build & Deploy Guide

## ✅ Status: 100% Complete

All components, pages, and features have been implemented with professional design, animations, and responsive layouts.

---

## 📦 Installation

Navigate to the LandingPage folder and install dependencies:

```bash
cd LandingPage
npm install
```

---

## 🛠️ Development

Start the development server to preview the landing page:

```bash
npm start
```

The landing page will open at `http://localhost:3000`

---

## 🏗️ Build for Production

Create an optimized production build:

```bash
npm run build
```

This creates a `build/` folder with optimized static files ready for deployment.

---

## 🌐 Deployment Options

### Option 1: Separate Domain (Recommended)

Deploy to `www.pointwave.ng` or `pointwave.ng`:

1. **Build the project:**
   ```bash
   cd LandingPage
   npm run build
   ```

2. **Upload to server:**
   ```bash
   # From your local machine
   scp -r build/* user@server:/path/to/www.pointwave.ng/
   ```

3. **Configure web server (Apache):**
   ```apache
   <VirtualHost *:80>
       ServerName pointwave.ng
       ServerAlias www.pointwave.ng
       DocumentRoot /path/to/www.pointwave.ng
       
       <Directory /path/to/www.pointwave.ng>
           Options -Indexes +FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
       
       # SPA routing
       <IfModule mod_rewrite.c>
           RewriteEngine On
           RewriteBase /
           RewriteRule ^index\.html$ - [L]
           RewriteCond %{REQUEST_FILENAME} !-f
           RewriteCond %{REQUEST_FILENAME} !-d
           RewriteRule . /index.html [L]
       </IfModule>
   </VirtualHost>
   ```

4. **Create .htaccess in build folder:**
   ```apache
   <IfModule mod_rewrite.c>
       RewriteEngine On
       RewriteBase /
       RewriteRule ^index\.html$ - [L]
       RewriteCond %{REQUEST_FILENAME} !-f
       RewriteCond %{REQUEST_FILENAME} !-d
       RewriteRule . /index.html [L]
   </IfModule>
   
   # Cache static assets
   <FilesMatch "\.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$">
       Header set Cache-Control "max-age=31536000, public"
   </FilesMatch>
   
   # Don't cache HTML
   <FilesMatch "\.(html|htm)$">
       Header set Cache-Control "no-cache, no-store, must-revalidate"
   </FilesMatch>
   ```

### Option 2: Subdirectory on Main Domain

Deploy to `app.pointwave.ng/landing`:

1. **Build the project:**
   ```bash
   cd LandingPage
   npm run build
   ```

2. **Update package.json homepage:**
   ```json
   {
     "homepage": "/landing"
   }
   ```

3. **Rebuild:**
   ```bash
   npm run build
   ```

4. **Upload to server:**
   ```bash
   scp -r build/* user@server:/home/aboksdfs/app.pointwave.ng/public/landing/
   ```

5. **Update Laravel routes (routes/web.php):**
   ```php
   // Serve landing page at /landing
   Route::get('/landing/{any?}', function () {
       return file_get_contents(public_path('landing/index.html'));
   })->where('any', '.*');
   ```

---

## 📁 What's Included

### Pages
- **Home** (`/`) - Hero, Partners, Features, For Startups, Coming Features
- **Company** (`/company`) - About, Mission, Vision, Contact Info
- **Developers** (`/developers`) - API docs, Quick start, Code examples
- **Pricing** (`/pricing`) - Plans, Transaction fees, CTA
- **Support** (`/support`) - Contact form, FAQs, Contact methods

### Features
- ✅ Responsive design (mobile, tablet, desktop)
- ✅ Floating animations
- ✅ Smooth transitions
- ✅ Professional color scheme (green/teal)
- ✅ All links configured to production URLs
- ✅ Newsletter subscription form
- ✅ Contact form with validation
- ✅ SEO-friendly structure

### Links Configuration
- Sign In: `https://app.pointwave.ng/auth/login`
- Sign Up: `https://app.pointwave.ng/auth/register`
- API Docs: `https://app.pointwave.ng/documentation/home`
- Support: `02014542876`
- Email: `support@pointwave.ng`
- Location: `Kano State, Nigeria`

---

## 🎨 Customization

### Update Colors
Edit `src/styles/index.css`:
```css
:root {
  --primary-green: #10b981;
  --primary-dark: #059669;
  --primary-light: #34d399;
  /* ... */
}
```

### Update Content
- **Company info:** Edit `src/pages/CompanyPage.js`
- **Pricing:** Edit `src/pages/PricingPage.js`
- **FAQs:** Edit `src/pages/SupportPage.js`
- **Features:** Edit `src/components/FeaturesSection.js`

### Add Images/Logos
1. Place images in `public/images/` or `src/assets/`
2. Import and use in components:
   ```jsx
   import logo from '../assets/logo.png';
   <img src={logo} alt="Logo" />
   ```

---

## 🧪 Testing

### Test Locally
```bash
npm start
```

### Test Production Build
```bash
npm run build
npx serve -s build
```

### Test Responsiveness
- Chrome DevTools (F12) → Toggle device toolbar
- Test on actual mobile devices
- Check all breakpoints: 320px, 768px, 1024px, 1440px

---

## 🔍 SEO Optimization

### Add to public/index.html:
```html
<meta name="description" content="PointWave - Nigeria's leading payment gateway platform. Accept payments, manage transactions, and grow your business.">
<meta name="keywords" content="payment gateway, nigeria, fintech, virtual accounts, bank transfers">
<meta property="og:title" content="PointWave - Payment Gateway Platform">
<meta property="og:description" content="Accept payments and grow your business with PointWave">
<meta property="og:image" content="https://pointwave.ng/og-image.jpg">
<meta property="og:url" content="https://pointwave.ng">
<meta name="twitter:card" content="summary_large_image">
```

---

## 📊 Performance

The landing page is optimized for:
- Fast loading times
- Minimal bundle size
- Smooth animations
- Mobile performance

### Further Optimization:
```bash
# Analyze bundle size
npm run build
npx source-map-explorer 'build/static/js/*.js'

# Compress images
# Use tools like TinyPNG or ImageOptim
```

---

## 🐛 Troubleshooting

### Issue: Blank page after deployment
**Solution:** Check browser console for errors. Ensure .htaccess is configured for SPA routing.

### Issue: Links not working
**Solution:** Verify all external links use full URLs (https://...).

### Issue: Styles not loading
**Solution:** Clear browser cache. Check that CSS files are in build/static/css/.

### Issue: 404 on page refresh
**Solution:** Configure server for SPA routing (see .htaccess above).

---

## 📞 Support

If you need help with deployment:
- Email: support@pointwave.ng
- Phone: 02014542876

---

## ✅ Deployment Checklist

- [ ] Install dependencies (`npm install`)
- [ ] Test locally (`npm start`)
- [ ] Build for production (`npm run build`)
- [ ] Test production build (`npx serve -s build`)
- [ ] Upload to server
- [ ] Configure web server (.htaccess)
- [ ] Test all pages and links
- [ ] Test on mobile devices
- [ ] Check browser console for errors
- [ ] Verify SEO meta tags
- [ ] Test contact form
- [ ] Monitor performance

---

**Status:** ✅ Ready for Deployment  
**Last Updated:** February 18, 2026  
**Version:** 1.0.0
