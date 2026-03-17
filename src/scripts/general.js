

window.onload = function () {
    // Parse all emojis on the page
    twemoji.parse(document.body, {
        folder: 'svg',
        ext: '.svg',
    });
    if (document.querySelector('#feedCompose')) {
        document.querySelector('#feedCompose').classList.add('swipe-up');
    }
};
function updatePostedDates() {
    const chirps = document.querySelectorAll('.chirp .postedDate');
    chirps.forEach(function (chirp) {
        const timestamp = chirp.getAttribute('data-timestamp');
        const postDate = new Date(parseInt(timestamp) * 1000);
        const now = new Date();
        const diffInMilliseconds = now - postDate;
        const diffInSeconds = Math.floor(diffInMilliseconds / 1000);
        const diffInMinutes = Math.floor(diffInSeconds / 60);
        const diffInHours = Math.floor(diffInMinutes / 60);
        const diffInDays = Math.floor(diffInHours / 24);

        let relativeTime;

        if (diffInSeconds < 60) {
            relativeTime = diffInSeconds + "s ago";
        } else if (diffInMinutes < 60) {
            relativeTime = diffInMinutes + "m ago";
        } else if (diffInHours < 24) {
            relativeTime = diffInHours + "h ago";
        } else if (diffInDays < 7) {
            relativeTime = diffInDays + "d ago";
        } else {
            // Format date as YYYY-MM-DD when it's more than a week ago
            const options = {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            };
            relativeTime = postDate.toLocaleDateString([], options);
        }

        chirp.textContent = relativeTime;
    });
}


document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('#feedCompose')) {
        const cancelChirpButton = document.querySelector('.cancelChirp');
        const saveDraftButton = document.getElementById('saveDraftButton');
        const discardDraftButton = document.getElementById('discardDraftButton');
        const cancelModal = document.getElementById('cancelModal');
        const draftsContainer = document.querySelector('.drafts-container');

        cancelChirpButton.onclick = function () {
            const chirpContent = document.querySelector('textarea[name="chirpComposeText"]').value;
            if (chirpContent.trim().length > 0) {
                cancelModal.style.display = "block";
            } else {
                slideDownPost();
            }
        };

        saveDraftButton.onclick = function () {
            const chirpContent = document.querySelector('textarea[name="chirpComposeText"]').value;
            if (chirpContent.trim().length > 0) {
                saveDraft(chirpContent);
            }
            slideDownPost();
        };

        discardDraftButton.onclick = function () {
            slideDownPost();
        };

        window.onclick = function (event) {
            if (event.target == cancelModal) {
                cancelModal.style.display = "none";
            }
        };

        function saveDraft(content) {
            let drafts = JSON.parse(localStorage.getItem('draftChirps')) || [];
            drafts.push({ id: Date.now(), content: content });
            localStorage.setItem('draftChirps', JSON.stringify(drafts));
            displayDrafts();
        }

        function displayDrafts() {
            const drafts = JSON.parse(localStorage.getItem('draftChirps')) || [];
            draftsContainer.innerHTML = drafts.length > 0 ? '' : '<div><p class="subText">You have no drafts.</p></div>';
            drafts.forEach((draft, index) => {
                const draftElement = document.createElement('div');
                draftElement.classList.add('draft');

                const draftText = document.createElement('p');
                draftText.innerText = draft.content;

                draftElement.addEventListener('click', () => {
                    const textarea = document.querySelector('textarea[name="chirpComposeText"]');
                    textarea.value = draft.content;
                    deleteDraft(index);
                    displayDrafts();
                });

                const deleteButton = document.createElement('button');
                deleteButton.innerText = 'Delete';
                deleteButton.addEventListener('click', (event) => {
                    event.stopPropagation();
                    deleteDraft(index);
                    displayDrafts();
                });

                draftElement.appendChild(draftText);
                draftElement.appendChild(deleteButton);
                draftsContainer.appendChild(draftElement);
            });
        }

        function deleteDraft(index) {
            let drafts = JSON.parse(localStorage.getItem('draftChirps')) || [];
            drafts.splice(index, 1);
            localStorage.setItem('draftChirps', JSON.stringify(drafts));
        }

        displayDrafts();
    }
});

function showMenuSettings() {
    document.getElementById("menuSettings").classList.toggle("visible");
    document.getElementById("settingsButtonWrapper").classList.toggle("clickedDown");
}

function slideDownPost() {
    var element = document.getElementById('feedCompose');
    element.classList.add('slideDown');
    setTimeout(back, 400);
}

function back() {
    if (document.referrer) {
        window.history.back();
    } else {
        window.location.href = '/';
    }
}


let chirpSound = null;

function playChirpSound() {
    if (chirpSound) {
        chirpSound.pause();
        chirpSound.currentTime = 0;
    }
    chirpSound = new Audio('/src/audio/whoLetTheBirdsOut.mp3');
    chirpSound.play().catch(error => console.error('Error playing sound:', error));
}


function openEditBannerModal() {
    document.getElementById('editBannerModal').style.display = 'block';
}

function closeEditBannerModal() {
    document.getElementById('editBannerModal').style.display = 'none';
}

function openEditProfilePicModal() {
    document.getElementById('editProfilePicModal').style.display = 'block';
}

function closeEditProfilePicModal() {
    document.getElementById('editProfilePicModal').style.display = 'none';
}

function closeWannaTalkAboutItModal() {
    document.getElementById('wannaTalkAboutItModal').style.display = 'none';
}

function openMoreOptionsModal() {
    document.getElementById('moreOptionsModal').classList.add('openOptions');
}

function closeMoreOptionsModal() {
    document.getElementById('moreOptionsModal').classList.remove('openOptions');
}

function openNewChirpModal() {
    document.getElementById('newChirpModal').style.display = 'flex';
}

function closeNewChirpModal() {
    document.getElementById('newChirpModal').style.display = 'none';
}

function openReplyModal() {
    document.getElementById('replyChirpModal').style.display = 'flex';
}

function closeReplyModal() {
    document.getElementById('replyChirpModal').style.display = 'none';
}




// Close the modal if the user clicks outside of it
window.onclick = function (event) {
    if (event.target == document.getElementById('editBannerModal')) {
        closeEditBannerModal();
    }
    if (event.target == document.getElementById('editProfilePicModal')) {
        closeEditProfilePicModal();
    }

};

function updateCharacterCount(textarea) {
    const maxChars = 500;
    const remainingChars = maxChars - textarea.value.length;
    const progressCircle = document.getElementById('progressCircle');
    const progressText = document.getElementById('progressText');
    const postButton = document.getElementById('postButton');

    const progress = Math.max(0, Math.min((textarea.value.length / maxChars) * 100, 100));

    // Update progress text and color
    progressText.textContent = `${remainingChars}`;
    if (remainingChars <= 0) {
        progressText.classList.add('red');
        postButton.disabled = true;
        progressCircle.classList.add('red-circle');  // Add red-circle class when limit is exceeded
    } else {
        progressText.classList.remove('red');
        postButton.disabled = false;
        progressCircle.classList.remove('red-circle');  // Remove red-circle class when text is under limit
    }

    // Update circle progress by manipulating the border color or similar visuals
    const degree = (textarea.value.length / maxChars) * 360;
    progressCircle.style.background = `conic-gradient(var(--accent-color) ${degree}deg, var(--contrastColor) ${degree}deg)`;
}

function updateCharacterCountReply(textarea) {
    const maxChars = 500;
    const remainingCharsReply = maxChars - textarea.value.length;
    const progressCircleReply = document.getElementById('progressCircleReply');
    const progressTextReply = document.getElementById('progressTextReply');
    const postButtonReply = document.getElementById('replyButton');

    const progress = Math.max(0, Math.min((textarea.value.length / maxChars) * 100, 100));

    // Update progress text and color
    progressTextReply.textContent = `${remainingCharsReply}`;
    if (remainingCharsReply <= 0) {
        progressTextReply.classList.add('red');
        postButtonReply.disabled = true;
        progressCircleReply.classList.add('red-circle');  // Add red-circle class when limit is exceeded
    } else {
        progressTextReply.classList.remove('red');
        postButtonReply.disabled = false;
        progressCircleReply.classList.remove('red-circle');  // Remove red-circle class when text is under limit
    }

    // Update circle progress by manipulating the border color or similar visuals
    const degree = (textarea.value.length / maxChars) * 360;
    progressCircleReply.style.background = `conic-gradient(var(--accent-color) ${degree}deg, var(--contrastColor) ${degree}deg)`;
}

const htmlEl = document.documentElement;

// Load theme and accent color from localStorage if available
const currentTheme = localStorage.getItem('theme') || 'auto';
const currentAccentColor = localStorage.getItem('accent-color') || '#1AD063';
const currentHoverAccentColor = localStorage.getItem('hover-accent-color') || '#128E3C';

// Apply theme and accent color as early as possible to avoid flash
const prefersDarkScheme = window.matchMedia("(prefers-color-scheme: dark)");
const applyInitialTheme = () => {
    if (currentTheme === 'auto') {
        htmlEl.dataset.theme = prefersDarkScheme.matches ? 'dark' : 'light';
    } else {
        htmlEl.dataset.theme = currentTheme;
    }
    htmlEl.style.setProperty('--accent-color', currentAccentColor);
    htmlEl.style.setProperty('--hover-color', currentHoverAccentColor);
};
applyInitialTheme();

// ── First-visit welcome banner ─────────────────────────────────────────────────
// Shows once per browser session (sessionStorage), never again after dismiss.
(function () {
    if (sessionStorage.getItem('neochirp_welcomed')) return;

    const banner = document.createElement('div');
    banner.id = 'neochirp-welcome-banner';
    banner.innerHTML = `
        <div id="neochirp-welcome-inner">
            <div id="neochirp-welcome-text">
                <strong>⚠️ NeoChirp — experimental &amp; unofficial</strong>
                <p>
                    This is an <strong>unofficial, experimental fork</strong> of
                    <a href="https://beta.chirpsocial.net/" target="_blank" rel="noopener noreferrer">Chirp</a>
                    (<a href="https://github.com/actuallyaridan/chirp" target="_blank" rel="noopener noreferrer">original repo</a>,
                    by Adnan Bukvic), maintained by
                    <a href="https://github.com/j4ckxyz" target="_blank" rel="noopener noreferrer">Jack Gilbert</a>
                    using Claude Code.
                    <strong>Not secure. Not stable. Data loss may occur.</strong>
                    For a reliable experience use the original Chirp instead.
                    Need an invite here?
                    <a href="https://twitter.com/jglypt" target="_blank" rel="noopener noreferrer">@jglypt on Twitter</a>
                    or
                    <a href="https://bsky.app/profile/j4ck.xyz" target="_blank" rel="noopener noreferrer">@j4ck.xyz on Bluesky</a>.
                </p>
            </div>
            <button id="neochirp-welcome-close" aria-label="Dismiss">Got it</button>
        </div>
    `;

    const style = document.createElement('style');
    style.textContent = `
        #neochirp-welcome-banner {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            z-index: 10000;
            background: var(--accent-color, #1AD063);
            color: #000;
            padding: 14px 20px;
            box-shadow: 0 -2px 16px rgba(0,0,0,.4);
            animation: nc-slide-up .35s cubic-bezier(.22,1,.36,1);
        }
        @keyframes nc-slide-up {
            from { transform: translateY(100%); }
            to   { transform: translateY(0);    }
        }
        #neochirp-welcome-banner.nc-hiding {
            animation: nc-slide-down .3s ease forwards;
        }
        @keyframes nc-slide-down {
            to { transform: translateY(110%); opacity: 0; }
        }
        #neochirp-welcome-inner {
            display: flex; align-items: center; gap: 16px;
            max-width: 900px; margin: 0 auto; flex-wrap: wrap;
        }
        #neochirp-welcome-text { flex: 1; min-width: 220px; font-size: .88rem; }
        #neochirp-welcome-text strong { display: block; font-size: 1rem; margin-bottom: 4px; }
        #neochirp-welcome-text p { margin: 0; line-height: 1.5; }
        #neochirp-welcome-text a { color: #000; text-decoration: underline; font-weight: 600; }
        #neochirp-welcome-close {
            flex-shrink: 0;
            background: rgba(0,0,0,.18); border: none; border-radius: 20px;
            padding: 8px 20px; font-size: .9rem; font-weight: 700;
            cursor: pointer; color: #000;
            transition: background .15s;
        }
        #neochirp-welcome-close:hover { background: rgba(0,0,0,.3); }
    `;

    document.head.appendChild(style);
    document.body.appendChild(banner);

    document.getElementById('neochirp-welcome-close').addEventListener('click', function () {
        banner.classList.add('nc-hiding');
        sessionStorage.setItem('neochirp_welcomed', '1');
        setTimeout(() => banner.remove(), 350);
    });
})();

document.addEventListener('DOMContentLoaded', () => {
    // Function to update theme based on "auto" mode
    const applyAutoTheme = () => {
        const theme = prefersDarkScheme.matches ? 'dark' : 'light';
        htmlEl.dataset.theme = theme;
    };

    // Function to update theme based on selection
    const toggleTheme = (theme) => {
        if (theme === 'auto') {
            htmlEl.dataset.theme = 'auto';
            localStorage.setItem('theme', 'auto');
            applyAutoTheme();
        } else {
            htmlEl.dataset.theme = theme;
            localStorage.setItem('theme', theme);
        }
    };

    // Listen for system dark mode changes in "auto" mode
    prefersDarkScheme.addEventListener('change', () => {
        if (htmlEl.dataset.theme === 'auto') {
            applyAutoTheme();
        }
    });

    // Function to update accent color
    const updateAccentColor = (color) => {
        htmlEl.style.setProperty('--accent-color', color);
        localStorage.setItem('accent-color', color);
        if  (color === '#FC2C6A') {
            htmlEl.style.setProperty('--hover-color', '#D4004D');
        } else if (color === '#FF8B1F') {
            htmlEl.style.setProperty('--hover-color', '#D57100');
        } else if (color === '#10BDF3') {
            htmlEl.style.setProperty('--hover-color', '#0A85BA');
        } else if (color === '#7A2BFC') {
            htmlEl.style.setProperty('--hover-color', '#5B1ECC');
        } else{
            htmlEl.style.setProperty('--hover-color', '#128E3C');
        }
        localStorage.setItem('hover-accent-color', htmlEl.style.getPropertyValue('--hover-color'));
    };

    // Theme radio buttons
    const themeRadios = document.querySelectorAll('input[name="theme"]');
    themeRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            toggleTheme(radio.value);
        });
        if (radio.value === currentTheme) {
            radio.checked = true;
        }
    });

    // Accent color radio buttons
    const colorRadios = document.querySelectorAll('input[name="accent_color"]');
    colorRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            updateAccentColor(radio.value);
        });
        if (radio.value === currentAccentColor) {
            radio.checked = true;
        }
    });
});
