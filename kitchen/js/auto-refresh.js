// Auto-refresh functionality for kitchen display
document.addEventListener('DOMContentLoaded', function() {
    // Set auto-refresh interval (in milliseconds)
    const refreshInterval = 30000; // 30 seconds
    
    // Function to refresh the page
    function refreshPage() {
        location.reload();
    }
    
    // Set up auto-refresh timer
    let refreshTimer = setTimeout(refreshPage, refreshInterval);
    
    // Reset timer when user interacts with the page
    document.addEventListener('click', function() {
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(refreshPage, refreshInterval);
    });
    
    // Add auto-refresh indicator to the page
    const refreshIndicator = document.createElement('div');
    refreshIndicator.style.position = 'fixed';
    refreshIndicator.style.bottom = '70px';
    refreshIndicator.style.right = '20px';
    refreshIndicator.style.backgroundColor = 'rgba(0,0,0,0.7)';
    refreshIndicator.style.color = 'white';
    refreshIndicator.style.padding = '5px 10px';
    refreshIndicator.style.borderRadius = '5px';
    refreshIndicator.style.fontSize = '12px';
    refreshIndicator.textContent = 'Auto-refresh: 30s';
    document.body.appendChild(refreshIndicator);
    
    // Update the countdown timer
    let countdown = 30;
    const countdownInterval = setInterval(function() {
        countdown--;
        if (countdown <= 0) {
            countdown = 30;
        }
        refreshIndicator.textContent = `Auto-refresh: ${countdown}s`;
    }, 1000);
    
    // Real-time timestamp updates
    function updateTimestamps() {
        const timers = document.querySelectorAll('.timer');
        timers.forEach(timer => {
            const currentText = timer.textContent;
            const match = currentText.match(/(\d+)m ago/);
            if (match) {
                const currentMinutes = parseInt(match[1]);
                const newMinutes = currentMinutes + 1;
                timer.textContent = `${newMinutes}m ago`;
                
                // Update timer classes based on elapsed time
                timer.className = 'timer';
                if (newMinutes >= 15) {
                    timer.classList.add('timer-danger');
                } else if (newMinutes >= 10) {
                    timer.classList.add('timer-warning');
                }
            }
        });
    }
    
    // Update timestamps every minute
    setInterval(updateTimestamps, 60000); // 60 seconds = 1 minute
});