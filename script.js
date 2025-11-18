// Pexels API Configuration
const PEXELS_API_KEY = 'YOUR_PEXELS_API_KEY'; // You'll need to get this from https://www.pexels.com/api/
const PEXELS_BASE_URL = 'https://api.pexels.com/v1';

// Global variables
let currentPage = 'home';
let userFlowVisible = false;
let annotationsVisible = false;

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
    setupEventListeners();
    loadImages();
    setupAnnotations();
    setupUserFlow();
});

// Initialize the application
function initializeApp() {
    // Set current page based on URL
    const path = window.location.pathname;
    if (path.includes('portfolio')) currentPage = 'portfolio';
    else if (path.includes('blog')) currentPage = 'blog';
    else if (path.includes('about')) currentPage = 'about';
    else currentPage = 'home';
    
    // Update active navigation
    updateActiveNavigation();
    
    // Initialize page-specific functionality
    if (currentPage === 'portfolio') {
        initializePortfolio();
    }
    
    // Add smooth scrolling
    document.documentElement.style.scrollBehavior = 'smooth';
}

// Setup event listeners
function setupEventListeners() {
    // Navigation links
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', handleNavigation);
    });
    
    // Portfolio filter buttons
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(btn => {
        btn.addEventListener('click', handlePortfolioFilter);
    });
    
    // Contact form
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', handleContactForm);
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', handleKeyboardShortcuts);
    
    // Window resize
    window.addEventListener('resize', handleWindowResize);
}

// Handle navigation
function handleNavigation(e) {
    e.preventDefault();
    const targetPage = e.target.getAttribute('data-page');
    
    // Add loading state
    document.body.classList.add('loading');
    
    // Navigate to page
    setTimeout(() => {
        window.location.href = e.target.href;
    }, 300);
}

// Update active navigation
function updateActiveNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('data-page') === currentPage) {
            link.classList.add('active');
        }
    });
}

// Load images from Pexels API
async function loadImages() {
    try {
        // For demo purposes, we'll use placeholder images since we need a Pexels API key
        // In production, you would replace this with actual Pexels API calls
        
        const imageUrls = [
            'https://images.pexels.com/photos/1040880/pexels-photo-1040880.jpeg?auto=compress&cs=tinysrgb&w=400',
            'https://images.pexels.com/photos/1040881/pexels-photo-1040881.jpeg?auto=compress&cs=tinysrgb&w=400',
            'https://images.pexels.com/photos/1040882/pexels-photo-1040882.jpeg?auto=compress&cs=tinysrgb&w=400',
            'https://images.pexels.com/photos/1040883/pexels-photo-1040883.jpeg?auto=compress&cs=tinysrgb&w=400',
            'https://images.pexels.com/photos/1040884/pexels-photo-1040884.jpeg?auto=compress&cs=tinysrgb&w=400'
        ];
        
        // Load gallery images
        const galleryItems = document.querySelectorAll('.gallery-item .image-placeholder');
        galleryItems.forEach((item, index) => {
            if (imageUrls[index]) {
                const img = document.createElement('img');
                img.src = imageUrls[index];
                img.alt = `Photography work ${index + 1}`;
                img.onload = () => {
                    item.innerHTML = '';
                    item.appendChild(img);
                };
            }
        });
        
        // Load portfolio images if on portfolio page
        if (currentPage === 'portfolio') {
            loadPortfolioImages();
        }
        
    } catch (error) {
        console.error('Error loading images:', error);
        showErrorMessage('Failed to load images. Please try again later.');
    }
}

// Load portfolio images
async function loadPortfolioImages() {
    const portfolioGrid = document.getElementById('portfolioGrid');
    if (!portfolioGrid) return;
    
    // Sample portfolio data
    const portfolioItems = [
        { category: 'portrait', title: 'Portrait Series', image: 'https://images.pexels.com/photos/1040880/pexels-photo-1040880.jpeg?auto=compress&cs=tinysrgb&w=400' },
        { category: 'fashion', title: 'Fashion Editorial', image: 'https://images.pexels.com/photos/1040881/pexels-photo-1040881.jpeg?auto=compress&cs=tinysrgb&w=400' },
        { category: 'lifestyle', title: 'Lifestyle Shoot', image: 'https://images.pexels.com/photos/1040882/pexels-photo-1040882.jpeg?auto=compress&cs=tinysrgb&w=400' },
        { category: 'commercial', title: 'Commercial Work', image: 'https://images.pexels.com/photos/1040883/pexels-photo-1040883.jpeg?auto=compress&cs=tinysrgb&w=400' },
        { category: 'portrait', title: 'Studio Portrait', image: 'https://images.pexels.com/photos/1040884/pexels-photo-1040884.jpeg?auto=compress&cs=tinysrgb&w=400' },
        { category: 'fashion', title: 'Fashion Campaign', image: 'https://images.pexels.com/photos/1040885/pexels-photo-1040885.jpeg?auto=compress&cs=tinysrgb&w=400' }
    ];
    
    portfolioGrid.innerHTML = '';
    
    portfolioItems.forEach(item => {
        const portfolioItem = createPortfolioItem(item);
        portfolioGrid.appendChild(portfolioItem);
    });
}

// Create portfolio item element
function createPortfolioItem(item) {
    const div = document.createElement('div');
    div.className = 'portfolio-item';
    div.setAttribute('data-category', item.category);
    
    div.innerHTML = `
        <div class="image-placeholder">
            <img src="${item.image}" alt="${item.title}" loading="lazy">
        </div>
        <div class="portfolio-content">
            <h4>${item.title}</h4>
            <span class="portfolio-category">${item.category}</span>
        </div>
    `;
    
    return div;
}

// Initialize portfolio functionality
function initializePortfolio() {
    // Portfolio filter functionality is handled in handlePortfolioFilter
}

// Handle portfolio filter
function handlePortfolioFilter(e) {
    const filter = e.target.getAttribute('data-filter');
    
    // Update active filter button
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    e.target.classList.add('active');
    
    // Filter portfolio items
    const portfolioItems = document.querySelectorAll('.portfolio-item');
    portfolioItems.forEach(item => {
        const category = item.getAttribute('data-category');
        if (filter === 'all' || category === filter) {
            item.style.display = 'block';
            item.style.animation = 'fadeInUp 0.5s ease';
        } else {
            item.style.display = 'none';
        }
    });
}

// Handle contact form submission
function handleContactForm(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const name = e.target.querySelector('input[type="text"]').value;
    const email = e.target.querySelector('input[type="email"]').value;
    const message = e.target.querySelector('textarea').value;
    
    // Simulate form submission
    showSuccessMessage('Thank you for your message! I\'ll get back to you soon.');
    e.target.reset();
}

// Setup annotations system
function setupAnnotations() {
    const annotatedElements = document.querySelectorAll('[data-annotation]');
    
    annotatedElements.forEach(element => {
        element.addEventListener('mouseenter', showAnnotation);
        element.addEventListener('mouseleave', hideAnnotation);
        element.addEventListener('click', toggleAnnotation);
    });
    
    // Add annotation toggle button
    createAnnotationToggle();
}

// Show annotation tooltip
function showAnnotation(e) {
    if (!annotationsVisible) return;
    
    const annotation = e.target.getAttribute('data-annotation');
    const tooltip = document.getElementById('annotationTooltip');
    
    if (annotation && tooltip) {
        tooltip.querySelector('.tooltip-content').textContent = annotation;
        tooltip.classList.add('active');
        
        // Position tooltip
        const rect = e.target.getBoundingClientRect();
        tooltip.style.left = rect.left + 'px';
        tooltip.style.top = (rect.top - 40) + 'px';
    }
}

// Hide annotation tooltip
function hideAnnotation() {
    const tooltip = document.getElementById('annotationTooltip');
    if (tooltip) {
        tooltip.classList.remove('active');
    }
}

// Toggle annotation
function toggleAnnotation(e) {
    e.preventDefault();
    annotationsVisible = !annotationsVisible;
    
    const toggleBtn = document.getElementById('annotationToggle');
    if (toggleBtn) {
        toggleBtn.textContent = annotationsVisible ? 'Hide Annotations' : 'Show Annotations';
        toggleBtn.classList.toggle('active', annotationsVisible);
    }
    
    // Update all annotated elements
    const annotatedElements = document.querySelectorAll('[data-annotation]');
    annotatedElements.forEach(element => {
        element.style.outline = annotationsVisible ? '2px solid #3498db' : 'none';
    });
}

// Create annotation toggle button
function createAnnotationToggle() {
    const toggleBtn = document.createElement('button');
    toggleBtn.id = 'annotationToggle';
    toggleBtn.className = 'annotation-toggle';
    toggleBtn.textContent = 'Show Annotations';
    toggleBtn.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        z-index: 1002;
        background: #3498db;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.9rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
    `;
    
    toggleBtn.addEventListener('click', toggleAnnotation);
    document.body.appendChild(toggleBtn);
}

// Setup user flow visualization
function setupUserFlow() {
    // Add user flow toggle button
    createUserFlowToggle();
    
    // Draw user flow connections
    drawUserFlowConnections();
}

// Create user flow toggle button
function createUserFlowToggle() {
    const toggleBtn = document.createElement('button');
    toggleBtn.id = 'userFlowToggle';
    toggleBtn.className = 'user-flow-toggle';
    toggleBtn.textContent = 'Show User Flow';
    toggleBtn.style.cssText = `
        position: fixed;
        top: 150px;
        right: 20px;
        z-index: 1002;
        background: #e74c3c;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.9rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
    `;
    
    toggleBtn.addEventListener('click', toggleUserFlow);
    document.body.appendChild(toggleBtn);
}

// Toggle user flow visualization
function toggleUserFlow() {
    userFlowVisible = !userFlowVisible;
    
    const overlay = document.getElementById('userFlowOverlay');
    const toggleBtn = document.getElementById('userFlowToggle');
    
    if (overlay && toggleBtn) {
        overlay.classList.toggle('active', userFlowVisible);
        toggleBtn.textContent = userFlowVisible ? 'Hide User Flow' : 'Show User Flow';
        toggleBtn.classList.toggle('active', userFlowVisible);
    }
}

// Draw user flow connections
function drawUserFlowConnections() {
    const overlay = document.getElementById('userFlowOverlay');
    const connections = overlay.querySelector('.flow-connections');
    
    if (!connections) return;
    
    // Clear existing connections
    connections.innerHTML = '';
    
    // Define user flow paths
    const flowPaths = [
        { from: '.brand', to: '.nav-link[data-page="portfolio"]', label: 'Brand to Portfolio' },
        { from: '.nav-link[data-page="home"]', to: '.gallery', label: 'Home to Gallery' },
        { from: '.nav-link[data-page="blog"]', to: '.blog-posts', label: 'Blog Navigation' },
        { from: '.nav-link[data-page="about"]', to: '.contact-section', label: 'About to Contact' }
    ];
    
    flowPaths.forEach(path => {
        const fromElement = document.querySelector(path.from);
        const toElement = document.querySelector(path.to);
        
        if (fromElement && toElement) {
            drawConnection(fromElement, toElement, path.label, connections);
        }
    });
}

// Draw connection line between elements
function drawConnection(fromElement, toElement, label, container) {
    const fromRect = fromElement.getBoundingClientRect();
    const toRect = toElement.getBoundingClientRect();
    
    const line = document.createElement('div');
    line.className = 'flow-line';
    line.setAttribute('data-label', label);
    
    // Calculate line position and dimensions
    const fromX = fromRect.left + fromRect.width / 2;
    const fromY = fromRect.top + fromRect.height / 2;
    const toX = toRect.left + toRect.width / 2;
    const toY = toRect.top + toRect.height / 2;
    
    const length = Math.sqrt(Math.pow(toX - fromX, 2) + Math.pow(toY - fromY, 2));
    const angle = Math.atan2(toY - fromY, toX - fromX) * 180 / Math.PI;
    
    line.style.left = fromX + 'px';
    line.style.top = fromY + 'px';
    line.style.width = length + 'px';
    line.style.transform = `rotate(${angle}deg)`;
    line.style.transformOrigin = '0 0';
    
    container.appendChild(line);
}

// Handle keyboard shortcuts
function handleKeyboardShortcuts(e) {
    // Toggle annotations with 'A' key
    if (e.key === 'a' || e.key === 'A') {
        e.preventDefault();
        toggleAnnotation({ preventDefault: () => {} });
    }
    
    // Toggle user flow with 'F' key
    if (e.key === 'f' || e.key === 'F') {
        e.preventDefault();
        toggleUserFlow();
    }
    
    // Escape to close all overlays
    if (e.key === 'Escape') {
        if (userFlowVisible) toggleUserFlow();
        if (annotationsVisible) toggleAnnotation({ preventDefault: () => {} });
    }
}

// Handle window resize
function handleWindowResize() {
    // Redraw user flow connections
    if (userFlowVisible) {
        setTimeout(() => {
            drawUserFlowConnections();
        }, 100);
    }
}

// Show success message
function showSuccessMessage(message) {
    showMessage(message, 'success');
}

// Show error message
function showErrorMessage(message) {
    showMessage(message, 'error');
}

// Show message
function showMessage(message, type) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message message-${type}`;
    messageDiv.textContent = message;
    messageDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1003;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: 500;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        animation: slideInRight 0.3s ease;
    `;
    
    if (type === 'success') {
        messageDiv.style.backgroundColor = '#27ae60';
    } else if (type === 'error') {
        messageDiv.style.backgroundColor = '#e74c3c';
    }
    
    document.body.appendChild(messageDiv);
    
    // Remove message after 3 seconds
    setTimeout(() => {
        messageDiv.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(messageDiv);
        }, 300);
    }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .annotation-toggle.active,
    .user-flow-toggle.active {
        transform: scale(1.05);
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    }
    
    .portfolio-content {
        padding: 1rem;
    }
    
    .portfolio-content h4 {
        margin-bottom: 0.5rem;
        color: #2c3e50;
    }
    
    .portfolio-category {
        background-color: #3498db;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.8rem;
        text-transform: capitalize;
    }
`;
document.head.appendChild(style);

// Utility function to fetch from Pexels API (requires API key)
async function fetchFromPexels(query, perPage = 5) {
    if (!PEXELS_API_KEY || PEXELS_API_KEY === 'YOUR_PEXELS_API_KEY') {
        console.warn('Pexels API key not configured. Using placeholder images.');
        return null;
    }
    
    try {
        const response = await fetch(`${PEXELS_BASE_URL}/search?query=${query}&per_page=${perPage}`, {
            headers: {
                'Authorization': PEXELS_API_KEY
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch from Pexels API');
        }
        
        const data = await response.json();
        return data.photos.map(photo => ({
            id: photo.id,
            url: photo.src.medium,
            alt: photo.alt,
            photographer: photo.photographer
        }));
    } catch (error) {
        console.error('Error fetching from Pexels:', error);
        return null;
    }
}

// Initialize smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add loading states
function addLoadingState(element) {
    element.classList.add('loading');
}

function removeLoadingState(element) {
    element.classList.remove('loading');
}

// Performance optimization: Lazy load images
function setupLazyLoading() {
    const images = document.querySelectorAll('img[loading="lazy"]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src || img.src;
                    img.classList.remove('loading');
                    observer.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    }
}

// Initialize lazy loading
setupLazyLoading();





