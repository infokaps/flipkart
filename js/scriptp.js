window.addEventListener('DOMContentLoaded', () => {
    const drawer = document.getElementById('drawer');
    const arrow = document.getElementById('arrow');
    drawer.classList.add('is-open');
    arrow.classList.add('rotate');
});

function toggleDrawer() {
    document.getElementById('drawer').classList.toggle('is-open');
    document.getElementById('arrow').classList.toggle('rotate');
}

function handleApply(btn, e) {
    e.stopPropagation();
    const allButtons = document.querySelectorAll('.apply-btn');
    const isAlreadyApplied = btn.innerText === "Applied";

    allButtons.forEach(b => {
        b.innerText = "Apply";
        b.style.color = "#2874f0";
    });

    if (!isAlreadyApplied) {
        btn.innerText = "Applied";
        btn.style.color = "#388e3c";
    }
}

const r1 = document.getElementById('row1');
const r2 = document.getElementById('row2');
let isSyncing = false;

r1.addEventListener('scroll', () => {
    if (!isSyncing) {
        isSyncing = true;
        r2.scrollLeft = r1.scrollLeft;
        setTimeout(() => { isSyncing = false; }, 10);
    }
});

r2.addEventListener('scroll', () => {
    if (!isSyncing) {
        isSyncing = true;
        r1.scrollLeft = r2.scrollLeft;
        setTimeout(() => { isSyncing = false; }, 10);
    }
});


// Ensure this is updated in your toggle function
function toggleDrawer() {
    const drawer = document.getElementById('drawer');
    const arrow = document.getElementById('arrow');
    
    drawer.classList.toggle('is-open');
    arrow.classList.toggle('rotate');
    
    // Note: CSS .is-open should have max-height: 3000px 
    // to accommodate these new sections.
}


