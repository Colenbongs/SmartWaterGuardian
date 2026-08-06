/**
 * Smart Water Guardian - Reviews JavaScript
 * Handles reviews, ratings, and testimonials
 */

let selectedRating = 5;
let currentFilter = 'all';

// ============================
// INITIALIZE REVIEWS
// ============================
document.addEventListener('DOMContentLoaded', function() {
    // Check auth state
    auth.onAuthStateChanged(function(user) {
        if (!user) {
            window.location.href = 'login.php';
            return;
        }
        
        // Initialize star rating
        setRating(5);
        
        // Load reviews
        loadReviews();
        
        // Set up event listeners
        setupReviewListeners();
    });
});

// ============================
// SETUP REVIEW LISTENERS
// ============================
function setupReviewListeners() {
    // Review form submission
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitReview();
        });
    }
    
    // Filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.textContent.trim().split(' ')[0];
            if (currentFilter === 'All') currentFilter = 'all';
            filterReviews(currentFilter);
        });
    });
    
    // Sort select
    const sortSelect = document.querySelector('.sort-select');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            sortReviews(this.value);
        });
    }
    
    // Helpful buttons
    document.querySelectorAll('.helpful-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const count = parseInt(this.textContent.match(/\d+/)?.[0] || 0);
            this.textContent = `👍 Helpful (${count + 1})`;
            this.style.background = '#c6f6d5';
            showToast('✅ Thanks for your feedback!', 'success');
        });
    });
}

// ============================
// LOAD REVIEWS
// ============================
function loadReviews() {
    // In a real app, this would load from Firebase
    // For now, we display static reviews with dynamic features
    
    // Update review count
    const reviewCount = document.querySelector('.rating-count');
    if (reviewCount) {
        const count = 1247 + Math.floor(Math.random() * 10);
        reviewCount.textContent = `Based on ${count.toLocaleString()} reviews 📝`;
    }
    
    // Update rating breakdown
    updateRatingBreakdown();
}

// ============================
// UPDATE RATING BREAKDOWN
// ============================
function updateRatingBreakdown() {
    const breakdowns = [
        { stars: 5, percent: 75 },
        { stars: 4, percent: 18 },
        { stars: 3, percent: 5 },
        { stars: 2, percent: 1.5 },
        { stars: 1, percent: 0.5 }
    ];
    
    const bars = document.querySelectorAll('.bar-fill');
    bars.forEach((bar, index) => {
        if (index < breakdowns.length) {
            bar.style.width = breakdowns[index].percent + '%';
        }
    });
}

// ============================
// SET RATING
// ============================
function setRating(rating) {
    selectedRating = rating;
    document.getElementById('ratingValue').value = rating;
    
    const stars = document.querySelectorAll('.star');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
}

// ============================
// SUBMIT REVIEW
// ============================
function submitReview() {
    const title = document.getElementById('reviewTitle')?.value;
    const text = document.getElementById('reviewText')?.value;
    const rating = document.getElementById('ratingValue')?.value;
    
    if (!title || !text) {
        showToast('⚠️ Please fill in all fields', 'warning');
        return;
    }
    
    if (title.length < 5) {
        showToast('⚠️ Title must be at least 5 characters', 'warning');
        return;
    }
    
    if (text.length < 20) {
        showToast('⚠️ Review must be at least 20 characters', 'warning');
        return;
    }
    
    // In a real app, save to Firebase
    // For now, show success
    showToast(`✅ Review submitted! 🎉\n⭐ Rating: ${rating} ★\n📝 "${title}"\n\nThank you for your feedback! 💚`, 'success', 5000);
    
    // Close modal and reset form
    closeReviewModal();
    document.getElementById('reviewForm')?.reset();
    setRating(5);
}

// ============================
// REVIEW MODAL
// ============================
function openReviewModal() {
    const modal = document.getElementById('reviewModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeReviewModal() {
    const modal = document.getElementById('reviewModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
    }
}

// ============================
// FILTER REVIEWS
// ============================
function filterReviews(filter) {
    const reviews = document.querySelectorAll('.review-card');
    
    reviews.forEach(review => {
        if (filter === 'all') {
            review.style.display = 'block';
            return;
        }
        
        const rating = review.querySelector('.review-rating');
        if (rating) {
            const starCount = rating.querySelectorAll('.fa-star').length;
            const filterNum = parseInt(filter);
            if (starCount === filterNum) {
                review.style.display = 'block';
            } else {
                review.style.display = 'none';
            }
        }
    });
    
    showToast(`🔍 Showing ${filter === 'all' ? 'all' : filter + '★'} reviews`, 'info', 1500);
}

// ============================
// SORT REVIEWS
// ============================
function sortReviews(sortType) {
    const reviewsContainer = document.querySelector('.reviews-list');
    if (!reviewsContainer) return;
    
    const reviews = Array.from(reviewsContainer.querySelectorAll('.review-card'));
    
    reviews.sort((a, b) => {
        switch(sortType) {
            case 'Most Recent 📅':
                const dateA = new Date(a.querySelector('.review-date')?.textContent.replace('📅 ', '') || 0);
                const dateB = new Date(b.querySelector('.review-date')?.textContent.replace('📅 ', '') || 0);
                return dateB - dateA;
                
            case 'Highest Rated ⭐':
                const ratingA = a.querySelectorAll('.fa-star').length;
                const ratingB = b.querySelectorAll('.fa-star').length;
                return ratingB - ratingA;
                
            case 'Most Helpful 👍':
                const helpfulA = parseInt(a.querySelector('.helpful-btn')?.textContent.match(/\d+/)?.[0] || 0);
                const helpfulB = parseInt(b.querySelector('.helpful-btn')?.textContent.match(/\d+/)?.[0] || 0);
                return helpfulB - helpfulA;
                
            default:
                return 0;
        }
    });
    
    // Re-append sorted reviews
    reviews.forEach(review => reviewsContainer.appendChild(review));
    showToast(`📊 Sorted by: ${sortType}`, 'info', 1500);
}

// ============================
// LOAD MORE REVIEWS
// ============================
document.addEventListener('DOMContentLoaded', function() {
    const loadMoreBtn = document.querySelector('.load-more .btn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            const reviewsContainer = document.querySelector('.reviews-list');
            if (reviewsContainer) {
                // Show loading state
                this.textContent = '⏳ Loading...';
                this.disabled = true;
                
                // Simulate loading
                setTimeout(() => {
                    this.textContent = '📖 Load More Reviews';
                    this.disabled = false;
                    showToast('🎉 More reviews loaded!', 'success');
                }, 1500);
            }
        });
    }
});

// ============================
// EXPOSE FUNCTIONS
// ============================
window.openReviewModal = openReviewModal;
window.closeReviewModal = closeReviewModal;
window.setRating = setRating;
window.filterReviews = filterReviews;
window.sortReviews = sortReviews;

console.log('⭐ Reviews module loaded successfully!');