# Ivan Bilbao Photography Portfolio

A high-fidelity, functional photography portfolio website built from a low-fidelity wireframe. This project demonstrates the complete transformation from wireframe to a fully functional, styled, and interactive website.

## Features

### ✅ Complete Web Pages
- **Home Page**: Landing page with hero section and featured gallery
- **Portfolio Page**: Filterable gallery with different photography categories
- **Blog Page**: Blog posts with articles and behind-the-scenes content
- **About Page**: Personal information, specialties, and contact form

### ✅ Annotations System
- **Interactive Annotations**: Hover over any element with `data-annotation` to see its function
- **Toggle Annotations**: Click the "Show Annotations" button or press 'A' key
- **Component Descriptions**: Each UI element is clearly labeled and explained

### ✅ User Flow Visualization
- **Visual Connections**: Lines connecting related elements to show user flow
- **Toggle User Flow**: Click the "Show User Flow" button or press 'F' key
- **Navigation Paths**: Clear visualization of how users move through the site

### ✅ Modern Design & Functionality
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile
- **Smooth Animations**: CSS animations and transitions for better UX
- **Interactive Elements**: Hover effects, button states, and form handling
- **Professional Styling**: Clean, modern design with proper typography

### ✅ Pexels Integration
- **Model Photography**: Integrated with Pexels API for high-quality model images
- **Dynamic Loading**: Images load dynamically based on category
- **Fallback System**: Graceful handling when API is unavailable

## Getting Started

### 1. Get Pexels API Key (Optional)
To use real images from Pexels:

1. Visit [Pexels API](https://www.pexels.com/api/)
2. Sign up for a free account
3. Get your API key
4. Replace `YOUR_PEXELS_API_KEY` in `script.js` with your actual key

```javascript
const PEXELS_API_KEY = 'your_actual_api_key_here';
```

### 2. Run the Website
Simply open `index.html` in your web browser. No server setup required!

### 3. Explore the Features

#### Annotations
- Click "Show Annotations" button (top-right)
- Or press 'A' key to toggle
- Hover over any highlighted element to see its function

#### User Flow
- Click "Show User Flow" button (top-right)
- Or press 'F' key to toggle
- See visual connections between related elements

#### Navigation
- Use the main navigation to move between pages
- All pages are fully functional with proper routing

## File Structure

```
├── index.html          # Home page
├── portfolio.html      # Portfolio gallery page
├── blog.html          # Blog posts page
├── about.html         # About and contact page
├── styles.css         # Complete CSS styling
├── script.js          # JavaScript functionality
└── README.md          # This file
```

## Technical Implementation

### HTML Structure
- Semantic HTML5 elements
- Proper accessibility attributes
- Data attributes for annotations and functionality
- Clean, organized markup

### CSS Features
- CSS Grid and Flexbox for layouts
- CSS Custom Properties (variables)
- Responsive design with mobile-first approach
- Modern CSS animations and transitions
- Professional color scheme and typography

### JavaScript Functionality
- ES6+ modern JavaScript
- Async/await for API calls
- Event delegation and handling
- Dynamic content loading
- Interactive features and animations
- Keyboard shortcuts for power users

## Design Philosophy

This project transforms a simple low-fidelity wireframe into a professional, high-fidelity website by:

1. **Maintaining Wireframe Structure**: All original elements are preserved and enhanced
2. **Adding Professional Styling**: Modern design principles and visual hierarchy
3. **Implementing Functionality**: Interactive features and dynamic content
4. **Ensuring Accessibility**: Proper semantic markup and keyboard navigation
5. **Optimizing Performance**: Lazy loading, efficient CSS, and optimized JavaScript

## Browser Support

- Chrome (recommended)
- Firefox
- Safari
- Edge
- Mobile browsers (iOS Safari, Chrome Mobile)

## Keyboard Shortcuts

- **A**: Toggle annotations
- **F**: Toggle user flow visualization
- **Escape**: Close all overlays

## Customization

### Colors
Edit the CSS custom properties in `styles.css`:
```css
:root {
    --primary-color: #3498db;
    --secondary-color: #2c3e50;
    --accent-color: #e74c3c;
}
```

### Content
- Update text content in HTML files
- Replace placeholder images with your own
- Modify the portfolio categories and blog posts

### Styling
- All styles are in `styles.css`
- Responsive breakpoints can be adjusted
- Animation timings and effects can be customized

## Performance Features

- **Lazy Loading**: Images load only when needed
- **Optimized CSS**: Efficient selectors and minimal repaints
- **Smooth Animations**: Hardware-accelerated transitions
- **Responsive Images**: Appropriate image sizes for different devices

## Future Enhancements

Potential improvements for the next version:
- Content Management System integration
- Advanced portfolio filtering
- Blog post management
- Contact form backend integration
- SEO optimization
- Progressive Web App features

## Credits

- **Design**: Based on low-fidelity wireframe by Ivan C. Bilbao
- **Images**: Pexels.com (with proper API integration)
- **Icons**: Font Awesome
- **Fonts**: Google Fonts (Inter)
- **Development**: Complete HTML, CSS, and JavaScript implementation

---

**Note**: This is a demonstration project showing the transformation from wireframe to functional website. All code is production-ready and follows modern web development best practices.





