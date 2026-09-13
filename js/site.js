const STORAGE_USERS = 'hydroUsers';
const STORAGE_CURRENT = 'hydroCurrentUser';
const STORAGE_FEED = 'hydroFeedPosts';
const STATIC_FEED_JSON = 'data/posts.json';

const defaultUsers = [
    { username: 'HydroAdmin', password: 'admin123', role: 'admin', email: 'admin@hydrobotics.com', location: 'Headquarters', age: '32', gender: 'Not specified', followers: 34, following: 19, posts: 5, likes: 142 },
    { username: 'InventorA', password: 'invent123', role: 'inventor', email: 'inventor@example.com', location: 'Tech City', age: '27', gender: 'Other', followers: 24, following: 18, posts: 9, likes: 58 }
];

const defaultFeedPosts = [
    { author: 'HydroAdmin', title: 'Welcome to HYDROBOTICS', body: 'This is the community feed preview page for the static GitHub Pages site.', timestamp: 'Today • 10:00 AM', likes: 12 },
    { author: 'InventorA', title: 'New Prototype Idea', body: 'Sharing a new design concept for a modular robotics system that adapts to field conditions.', timestamp: 'Today • 9:20 AM', likes: 8 },
    { author: 'HydroAdmin', title: 'Project Update', body: 'Our logistics platform now supports safer scheduling, equipment tracking, and collaboration.', timestamp: 'Yesterday • 5:30 PM', likes: 15 }
];

function getUsers() {
    const raw = localStorage.getItem(STORAGE_USERS);
    if (!raw) {
        localStorage.setItem(STORAGE_USERS, JSON.stringify(defaultUsers));
        return [...defaultUsers];
    }
    try {
        return JSON.parse(raw) || [];
    } catch {
        localStorage.removeItem(STORAGE_USERS);
        return [...defaultUsers];
    }
}

function saveUsers(users) {
    localStorage.setItem(STORAGE_USERS, JSON.stringify(users));
}

function getCurrentUser() {
    const raw = localStorage.getItem(STORAGE_CURRENT);
    if (!raw) {
        return null;
    }
    try {
        return JSON.parse(raw);
    } catch {
        localStorage.removeItem(STORAGE_CURRENT);
        return null;
    }
}

function setCurrentUser(user) {
    localStorage.setItem(STORAGE_CURRENT, JSON.stringify(user));
}

function logoutUser() {
    localStorage.removeItem(STORAGE_CURRENT);
    const userChip = document.getElementById('userChip');
    const authLink = document.getElementById('authLink');
    const signOutLink = document.getElementById('signOutLink');

    if (userChip) userChip.hidden = true;
    if (authLink) {
        authLink.textContent = 'Login';
        authLink.href = 'login.html';
        authLink.onclick = null;
    }
    if (signOutLink) signOutLink.remove();

    window.location.href = 'index.html';
}

function applyDarkMode(enabled = localStorage.getItem('hydroDarkMode') === 'true') {
    document.body.classList.toggle('dark-mode', enabled);
    return enabled;
}

function getFeedPosts() {
    const raw = localStorage.getItem(STORAGE_FEED);
    if (!raw) return [];
    try {
        return JSON.parse(raw) || [];
    } catch {
        localStorage.removeItem(STORAGE_FEED);
        return [];
    }
}

function saveFeedPosts(posts) {
    localStorage.setItem(STORAGE_FEED, JSON.stringify(posts));
}

function escapeHtml(text) {
    return String(text || '').replace(/[&<>"]+/g, match => {
        const replacements = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' };
        return replacements[match] || match;
    });
}

function formatPostText(text) {
    if (!text) return '';
    return escapeHtml(text).replace(/\n/g, '<br>');
}

function normalizeFeedPost(post) {
    const mediaFile = post.media_file || post.image || '';
    const mediaType = post.media_type || (post.image ? 'image' : '');
    return {
        author: post.author || post.username || 'HYDROBOTICS',
        title: post.title || 'Untitled Post',
        body: post.content || post.body || '',
        timestamp: post.created_at || post.timestamp || 'Unknown time',
        likes: Array.isArray(post.likes) ? post.likes.length : (typeof post.likes === 'number' ? post.likes : 0),
        comments: Array.isArray(post.comments) ? post.comments.length : 0,
        shares: post.shares || 0,
        reposts: post.reposts || 0,
        mediaSrc: mediaFile ? `data/uploads/${mediaFile}` : '',
        mediaType,
        imageText: post.image_text || '',
    };
}

function getStaticFeedPosts() {
    if (window.staticFeedPosts && Array.isArray(window.staticFeedPosts)) {
        return Promise.resolve(window.staticFeedPosts);
    }

    return fetch(STATIC_FEED_JSON)
        .then(response => {
            if (!response.ok) throw new Error('Feed data not available');
            return response.json();
        })
        .then(data => Array.isArray(data) ? data : [])
        .catch(() => {
            return window.staticFeedPosts && Array.isArray(window.staticFeedPosts)
                ? window.staticFeedPosts
                : [];
        });
}

function renderPostMedia(post) {
    if (!post.mediaSrc) return '';
    if (post.mediaType === 'video') {
        return `
            <div class="post-media">
                <video controls preload="metadata" src="${escapeHtml(post.mediaSrc)}"></video>
                ${post.imageText ? `<p class="text-muted">${escapeHtml(post.imageText)}</p>` : ''}
            </div>
        `;
    }
    return `
        <div class="post-media">
            <img src="${escapeHtml(post.mediaSrc)}" alt="${escapeHtml(post.imageText || post.title)}">
            ${post.imageText ? `<p class="text-muted">${escapeHtml(post.imageText)}</p>` : ''}
        </div>
    `;
}

function showNavUser() {
    const authLink = document.getElementById('authLink');
    if (!authLink) return;

    let userChip = document.getElementById('userChip');
    if (!userChip) {
        userChip = document.createElement('span');
        userChip.id = 'userChip';
        userChip.className = 'user-chip';
        userChip.hidden = true;
        authLink.parentElement.insertBefore(userChip, authLink);
    }

    let signOutLink = document.getElementById('signOutLink');
    const user = getCurrentUser();

    if (user) {
        if (!signOutLink) {
            signOutLink = document.createElement('a');
            signOutLink.id = 'signOutLink';
            signOutLink.className = 'btn-secondary sign-out-link';
            signOutLink.href = '#';
            signOutLink.textContent = 'Sign out';
            signOutLink.addEventListener('click', event => {
                event.preventDefault();
                logoutUser();
            });
            authLink.parentElement.appendChild(signOutLink);
        }

        userChip.hidden = false;
        userChip.textContent = `Logged in as ${user.username}`;
        authLink.textContent = 'Account';
        authLink.href = 'account.html';
        authLink.onclick = null;
        signOutLink.hidden = false;
    } else {
        if (signOutLink) signOutLink.remove();
        userChip.remove();
        authLink.textContent = 'Login';
        authLink.href = 'login.html';
        authLink.onclick = null;
    }
}

let lastScrollPosition = window.scrollY || 0;

function updateHeaderActionVisibility() {
    const authLink = document.getElementById('authLink');
    const navRight = document.querySelector('.nav-right');
    if (!authLink || !navRight) return;

    const currentScroll = window.scrollY || 0;
    const scrollingUp = currentScroll < lastScrollPosition;

    if (currentScroll <= 12) {
        document.body.classList.remove('header-actions-hidden');
        document.body.classList.add('header-actions-visible');
        navRight.classList.remove('is-hidden');
        navRight.style.opacity = '1';
        navRight.style.visibility = 'visible';
    } else if (scrollingUp) {
        document.body.classList.remove('header-actions-hidden');
        document.body.classList.add('header-actions-visible');
        navRight.classList.remove('is-hidden');
        navRight.style.opacity = '1';
        navRight.style.visibility = 'visible';
    } else {
        document.body.classList.add('header-actions-hidden');
        document.body.classList.remove('header-actions-visible');
        navRight.classList.add('is-hidden');
        navRight.style.opacity = '0';
        navRight.style.visibility = 'hidden';
    }

    lastScrollPosition = currentScroll;
}

function showAlert(containerId, message, type = 'info') {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
}

function clearAlert(containerId) {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = '';
    }
}

function switchAuthTab(tab) {
    const loginTab = document.getElementById('loginTab');
    const registerTab = document.getElementById('registerTab');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    if (!loginTab || !registerTab || !loginForm || !registerForm) return;

    if (tab === 'register') {
        loginTab.classList.remove('active');
        registerTab.classList.add('active');
        loginForm.hidden = true;
        registerForm.hidden = false;
    } else {
        loginTab.classList.add('active');
        registerTab.classList.remove('active');
        loginForm.hidden = false;
        registerForm.hidden = true;
    }
}

function handleLoginForm(event) {
    event.preventDefault();
    const username = document.getElementById('loginUsername')?.value.trim();
    const password = document.getElementById('loginPassword')?.value;
    const users = getUsers();
    const user = users.find(u => u.username.toLowerCase() === username.toLowerCase() && u.password === password);
    if (!user) {
        showAlert('loginAlert', 'Login failed. Please check your username and password.', 'error');
        return;
    }
    setCurrentUser(user);
    showNavUser();
    window.location.href = 'index.html';
}

function handleRegisterForm(event) {
    event.preventDefault();
    const username = document.getElementById('registerUsername')?.value.trim();
    const password = document.getElementById('registerPassword')?.value;
    const email = document.getElementById('registerEmail')?.value.trim();
    const location = document.getElementById('registerLocation')?.value.trim();
    const role = document.getElementById('registerRole')?.value;
    if (!username || !password || !email) {
        showAlert('loginAlert', 'Please complete every required field to register.', 'error');
        return;
    }
    const users = getUsers();
    if (users.some(u => u.username.toLowerCase() === username.toLowerCase())) {
        showAlert('loginAlert', 'That username is already taken. Please choose another.', 'error');
        return;
    }
    const newUser = {
        username,
        password,
        email,
        location: location || 'Not specified',
        role: role || 'member',
        age: document.getElementById('registerAge')?.value || 'Not specified',
        gender: document.getElementById('registerGender')?.value || 'Not specified',
        followers: 0,
        following: 0,
        posts: 0,
        likes: 0
    };
    users.push(newUser);
    saveUsers(users);
    setCurrentUser(newUser);
    window.location.href = 'inventor.html';
}

function renderFeedPage() {
    const feedContainer = document.getElementById('feedPosts');
    const formSection = document.getElementById('newPostSection');
    const user = getCurrentUser();
    if (!feedContainer) return;

    if (!user) {
        showAlert('feedAlert', 'You are not logged in. Please log in to create posts and save activity in the site preview.', 'info');
        if (formSection) formSection.hidden = true;
    } else {
        clearAlert('feedAlert');
        if (formSection) formSection.hidden = false;
    }

    feedContainer.innerHTML = '<div class="panel"><p class="text-muted">Loading feed posts…</p></div>';

    getStaticFeedPosts().then(staticPosts => {
        const localPosts = getFeedPosts();
        let feedPosts = [];

        if (staticPosts.length) {
            feedPosts = [...localPosts, ...staticPosts];
        } else if (localPosts.length) {
            feedPosts = localPosts;
        } else {
            feedPosts = defaultFeedPosts;
        }

        if (feedPosts.length === 0) {
            feedContainer.innerHTML = '<div class="panel"><p class="text-muted">No posts are available yet.</p></div>';
            return;
        }

        feedContainer.innerHTML = feedPosts.map(rawPost => {
            const post = normalizeFeedPost(rawPost);
            const metaParts = [];
            if (post.likes) metaParts.push(`${post.likes} likes`);
            if (post.comments) metaParts.push(`${post.comments} comments`);
            if (post.shares) metaParts.push(`${post.shares} shares`);
            if (post.reposts) metaParts.push(`${post.reposts} reposts`);
            const metaText = metaParts.length ? `<p class="post-meta">${metaParts.join(' • ')}</p>` : '<p class="post-meta text-muted">No reactions yet.</p>';

            return `
                <article class="post-card">
                    <h3>${escapeHtml(post.title)}</h3>
                    <p class="text-muted">${escapeHtml(post.author)} • ${escapeHtml(post.timestamp)}</p>
                    ${post.body ? `<p>${formatPostText(post.body)}</p>` : ''}
                    ${renderPostMedia(post)}
                    ${metaText}
                    <div class="post-actions" role="group" aria-label="Post actions">
                        <button class="post-action" type="button" data-feed-action="like">Like</button>
                        <button class="post-action" type="button" data-feed-action="comment">Comment</button>
                        <button class="post-action" type="button" data-feed-action="share">Share</button>
                    </div>
                </article>
            `;
        }).join('');
    }).catch(() => {
        const fallbackPosts = getFeedPosts().length ? getFeedPosts() : defaultFeedPosts;
        feedContainer.innerHTML = fallbackPosts.map(rawPost => {
            const post = normalizeFeedPost(rawPost);
            return `
                <article class="post-card">
                    <h3>${escapeHtml(post.title)}</h3>
                    <p class="text-muted">${escapeHtml(post.author)} • ${escapeHtml(post.timestamp)}</p>
                    ${post.body ? `<p>${formatPostText(post.body)}</p>` : ''}
                    ${renderPostMedia(post)}
                    <p class="post-meta text-muted">${post.likes} likes • ${post.comments} comments</p>
                    <div class="post-actions" role="group" aria-label="Post actions">
                        <button class="post-action" type="button" data-feed-action="like">Like</button>
                        <button class="post-action" type="button" data-feed-action="comment">Comment</button>
                        <button class="post-action" type="button" data-feed-action="share">Share</button>
                    </div>
                </article>
            `;
        }).join('');
    });
}

function initSocialFeedActions() {
    const feedContainer = document.getElementById('feedPosts');
    if (!feedContainer || feedContainer.dataset.actionsBound) return;
    feedContainer.dataset.actionsBound = 'true';
    feedContainer.addEventListener('click', event => {
        const action = event.target.closest('[data-feed-action]');
        if (!action) return;
        const actionName = action.dataset.feedAction;
        if (actionName === 'like') {
            action.classList.toggle('active');
            action.textContent = action.classList.contains('active') ? 'Liked' : 'Like';
        } else if (actionName === 'comment') {
            action.classList.add('active');
            action.textContent = 'Commenting';
        } else if (actionName === 'share') {
            action.classList.add('active');
            action.textContent = 'Shared';
        }
    });
}

function handleFeedPost(event) {
    event.preventDefault();
    const title = document.getElementById('postTitle')?.value.trim();
    const body = document.getElementById('postBody')?.value.trim();
    const user = getCurrentUser();
    if (!user) {
        showAlert('feedAlert', 'Please login before creating a post.', 'error');
        return;
    }
    if (!title || !body) {
        showAlert('feedAlert', 'Please provide both a title and body for your post.', 'error');
        return;
    }
    const posts = getFeedPosts();
    const newPost = {
        author: user.username,
        title,
        body,
        timestamp: 'Just now',
        likes: 0
    };
    posts.unshift(newPost);
    saveFeedPosts(posts);
    const users = getUsers();
    const userIndex = users.findIndex(u => u.username === user.username);
    if (userIndex !== -1) {
        users[userIndex].posts = (users[userIndex].posts || 0) + 1;
        saveUsers(users);
        setCurrentUser(users[userIndex]);
        showNavUser();
    }
    document.getElementById('postTitle').value = '';
    document.getElementById('postBody').value = '';
    showAlert('feedAlert', 'Your post is now visible in the feed.', 'success');
    renderFeedPage();
}

function renderDashboardPage() {
    const user = getCurrentUser();
    const summaryContainer = document.getElementById('dashboardSummary');
    const detailsContainer = document.getElementById('dashboardDetails');
    if (!summaryContainer || !detailsContainer) return;

    if (!user) {
        summaryContainer.innerHTML = '<p class="text-muted">Log in to see your personal dashboard stats, recent activity, and account summary.</p>';
        detailsContainer.innerHTML = '<div class="panel"><p class="text-muted">Dashboard preview only. Create an account and log in to personalize the experience.</p></div>';
        return;
    }

    const users = getUsers();
    const totalUsers = users.length;
    const totalPosts = getFeedPosts().length;
    summaryContainer.innerHTML = `
        <div class="stats-grid">
            <div class="stat-box"><strong>${totalUsers}</strong>Total Users</div>
            <div class="stat-box"><strong>${totalPosts}</strong>Feed Posts</div>
            <div class="stat-box"><strong>${user.posts || 0}</strong>Your Posts</div>
            <div class="stat-box"><strong>${user.likes || 0}</strong>Total Likes</div>
        </div>
    `;
    detailsContainer.innerHTML = `
        <div class="panel">
            <h2>Recent Activity</h2>
            <p>Welcome, ${user.username}. Your dashboard shows your account activity, post counts, and community engagement.</p>
        </div>
        <div class="panel">
            <h2>Quick Links</h2>
            <p><a href="feed.html">Go to Social Feed</a> | <a href="account.html">Profile</a> | <a href="settings.html">Settings</a> | <a href="videos.html">Videos</a></p>
        </div>
    `;
}

function renderAccountPage() {
    const user = getCurrentUser();
    const accountContainer = document.getElementById('accountContent');
    if (!accountContainer) return;

    if (!user) {
        accountContainer.innerHTML = '<div class="panel"><p class="text-muted">Login to view your account profile, follower counts, and recent posts.</p></div>';
        return;
    }

    accountContainer.innerHTML = `
        <div class="panel">
            <h1>${user.username}</h1>
            <p class="text-muted">${user.role.toUpperCase()} • ${user.email}</p>
            <div class="stats-grid" style="margin-top:1.5rem;">
                <div class="stat-box"><strong>${user.posts || 0}</strong>Posts</div>
                <div class="stat-box"><strong>${user.followers || 0}</strong>Followers</div>
                <div class="stat-box"><strong>${user.following || 0}</strong>Following</div>
                <div class="stat-box"><strong>${user.likes || 0}</strong>Likes</div>
            </div>
        </div>
        <div class="panel">
            <h2>Profile Summary</h2>
            <p>Location: ${user.location}</p>
            <p>Age: ${user.age}</p>
            <p>Gender: ${user.gender}</p>
        </div>
        <div class="panel">
            <h2>Your Latest Post</h2>
            <p>Use the feed page to publish updates, ideas, and recruiter messages for the HYDROBOTICS community.</p>
        </div>
    `;
}

function initSettingsPage() {
    const user = getCurrentUser();
    if (!user) {
        showAlert('settingsMessage', 'Log in to update your settings and save profile preferences.', 'info');
        document.getElementById('settingsForm')?.querySelectorAll('input, select')?.forEach(input => input.disabled = true);
        return;
    }
    document.getElementById('settingsGender').value = user.gender || 'Not specified';
    document.getElementById('settingsAge').value = user.age || '';
    document.getElementById('settingsLocation').value = user.location || '';
    document.getElementById('settingsEmail').value = user.email || '';
    document.getElementById('settingsPhone').value = user.phone || '';
}

function handleSettingsSubmit(event) {
    event.preventDefault();
    const user = getCurrentUser();
    if (!user) {
        showAlert('settingsMessage', 'Please log in to update your settings.', 'error');
        return;
    }
    const users = getUsers();
    const index = users.findIndex(u => u.username === user.username);
    if (index === -1) return;
    users[index].gender = document.getElementById('settingsGender')?.value || user.gender;
    users[index].age = document.getElementById('settingsAge')?.value || user.age;
    users[index].location = document.getElementById('settingsLocation')?.value || user.location;
    users[index].email = document.getElementById('settingsEmail')?.value || user.email;
    users[index].phone = document.getElementById('settingsPhone')?.value || user.phone || '';
    saveUsers(users);
    setCurrentUser(users[index]);
    showNavUser();
    showAlert('settingsMessage', 'Your settings have been saved locally in this browser.', 'success');
}

function initSettingsOptions() {
    const sections = document.querySelectorAll('.settings-section');
    const menuItems = document.querySelectorAll('[data-settings-section]');
    const showSection = sectionId => {
        sections.forEach(section => section.classList.toggle('active', section.id === sectionId));
        menuItems.forEach(item => item.classList.toggle('active', item.dataset.settingsSection === sectionId));
    };
    menuItems.forEach(item => item.addEventListener('click', () => showSection(item.dataset.settingsSection)));
    document.querySelectorAll('[data-settings-link]').forEach(link => link.addEventListener('click', event => {
        event.preventDefault();
        showSection(link.dataset.settingsLink);
    }));

    const darkMode = applyDarkMode();
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (darkModeToggle) {
        darkModeToggle.checked = darkMode;
        darkModeToggle.addEventListener('change', () => {
            localStorage.setItem('hydroDarkMode', String(darkModeToggle.checked));
            applyDarkMode(darkModeToggle.checked);
        });
    }

    document.getElementById('settingsLogout')?.addEventListener('click', logoutUser);
    document.getElementById('saveLanguage')?.addEventListener('click', () => {
        localStorage.setItem('hydroLanguage', document.getElementById('settingsLanguage').value);
        showAlert('settingsMessage', 'Language preference saved.', 'success');
    });
    const savedLanguage = localStorage.getItem('hydroLanguage');
    if (savedLanguage && document.getElementById('settingsLanguage')) document.getElementById('settingsLanguage').value = savedLanguage;
    document.getElementById('passwordForm')?.addEventListener('submit', event => {
        event.preventDefault();
        const user = getCurrentUser();
        const current = document.getElementById('currentPassword').value;
        const next = document.getElementById('newPassword').value;
        const confirmation = document.getElementById('confirmPassword').value;
        if (!user || current !== user.password) return showAlert('settingsMessage', 'Current password is incorrect.', 'error');
        if (next !== confirmation) return showAlert('settingsMessage', 'New passwords do not match.', 'error');
        const users = getUsers();
        const index = users.findIndex(item => item.username === user.username);
        users[index].password = next;
        saveUsers(users);
        setCurrentUser(users[index]);
        event.target.reset();
        showAlert('settingsMessage', 'Password updated successfully.', 'success');
    });
    document.getElementById('reportForm')?.addEventListener('submit', event => {
        event.preventDefault();
        event.target.reset();
        showAlert('settingsMessage', 'Thanks. Your report has been recorded for this demo.', 'success');
    });
}

function initInventorPage() {
    const profileForm = document.getElementById('inventorProfileForm');
    const profileMessage = document.getElementById('inventorProfileMessage');
    if (!profileForm || !profileMessage) return;

    profileForm.addEventListener('submit', event => {
        event.preventDefault();
        const profile = {
            name: document.getElementById('inventorName').value.trim(),
            type: document.getElementById('inventorType').value,
            focus: document.getElementById('inventorFocus').value.trim(),
            support: document.getElementById('inventorSupport').value.trim(),
            contact: document.getElementById('inventorContact').value.trim(),
            closedDeal: document.getElementById('closedDealConsent').checked,
            copyright: document.getElementById('copyrightConsent').checked,
            createdAt: new Date().toISOString()
        };
        const user = getCurrentUser();
        const profiles = JSON.parse(localStorage.getItem('hydroInventorProfiles') || '[]');
        profiles.push({ ...profile, username: user?.username || 'guest' });
        localStorage.setItem('hydroInventorProfiles', JSON.stringify(profiles));
        finishInventorProfile(profile, user);
    });

    document.getElementById('proposalForm')?.addEventListener('submit', event => {
        event.preventDefault();
        const user = getCurrentUser();
        const message = document.getElementById('proposalDetails').value.trim();
        const request = { action: 'help_request', username: user?.username || 'guest', message };
        const requests = JSON.parse(localStorage.getItem('hydroHelpRequests') || '[]');
        requests.push({ ...request, status: 'pending', createdAt: new Date().toISOString() });
        localStorage.setItem('hydroHelpRequests', JSON.stringify(requests));
        event.target.reset();
        profileMessage.innerHTML = '<div class="alert alert-success">Your proposal request was saved in this browser.</div>';
    });
}

function finishInventorProfile(profile, user) {
    const username = user?.username || profile.name.replace(/\s+/g, '').toLowerCase() || `member${Date.now()}`;
    const users = getUsers();
    let account = users.find(item => item.username.toLowerCase() === username.toLowerCase());
    if (!account) {
        account = { username, password: '', email: profile.contact, location: 'Not specified', role: profile.type, posts: 0, likes: 0, followers: 0, following: 0 };
        users.push(account);
        saveUsers(users);
    }
    setCurrentUser(account);
    window.location.href = 'inventor-community.html';
}

const communitySeedPosts = [
    { author: 'HYDROBOTICS Lab', role: 'business', type: 'proposal', title: 'Looking for a robotics enclosure partner', body: 'We are looking for a manufacturer who can help turn a field robotics prototype into a small production run.', contact: 'Reply with your capabilities and lead time.', closedDeal: false, timestamp: 'Today' },
    { author: 'Parts Network', role: 'partner', type: 'special', title: 'Prototype assembly special', body: 'Special pricing for first-time board assembly orders and small-batch component sourcing.', contact: 'Visit the supplier page for current details.', closedDeal: false, timestamp: 'Yesterday' },
    { author: 'CAD Helper', role: 'partner', type: 'help', title: 'CAD and design review available', body: 'I can help review manufacturability, tolerances, and 3D-print-ready files.', contact: 'Message through your agreed project channel.', closedDeal: true, timestamp: 'Yesterday' },
    { author: 'Northstar Ventures', role: 'investor', type: 'proposal', title: 'Seeking practical climate-tech prototypes', body: 'We are looking for early-stage teams with a working prototype and a clear path to field testing.', contact: 'Share a short overview and traction summary.', closedDeal: false, timestamp: 'Today' }
];

let activeCommunityFilter = 'all';

function getCommunityPosts() {
    const raw = localStorage.getItem('hydroCommunityPosts');
    if (!raw) return [...communitySeedPosts];
    try { return JSON.parse(raw) || []; } catch { return [...communitySeedPosts]; }
}

function renderCommunityPosts() {
    const container = document.getElementById('communityPosts');
    if (!container) return;
    const posts = getCommunityPosts().filter(post => activeCommunityFilter === 'all' || post.role === activeCommunityFilter || (activeCommunityFilter === 'partner' && ['manufacturer', 'technical partner', 'partner'].includes(post.role)));
    container.innerHTML = posts.map(post => `
        <article class="community-post panel">
            <div class="community-post-top"><span class="post-type-badge post-type-${escapeHtml(post.type)}">${escapeHtml(post.type)}</span><span class="community-role">${escapeHtml(post.role || 'community member')}</span><span class="text-muted">${escapeHtml(post.timestamp || 'Just now')}</span></div>
            <h3>${escapeHtml(post.title)}</h3>
            <p class="text-muted">${escapeHtml(post.author)} is open to new conversations</p>
            <p>${formatPostText(post.body)}</p>
            <div class="community-contact-row"><p><strong>Contact route:</strong> ${escapeHtml(post.contact)}</p><button class="btn-secondary community-contact-button" type="button" data-contact-name="${escapeHtml(post.author)}">Contact</button></div>
            ${post.closedDeal ? '<p class="closed-deal-note">Closed-deal conversation: agree on confidentiality before sharing protected information.</p>' : ''}
        </article>
    `).join('') || '<div class="panel"><p class="text-muted">No community posts yet.</p></div>';
}

function initCommunityPage() {
    renderCommunityPosts();
    document.getElementById('refreshCommunityFeed')?.addEventListener('click', renderCommunityPosts);
    document.querySelectorAll('[data-community-filter]').forEach(button => button.addEventListener('click', () => {
        activeCommunityFilter = button.dataset.communityFilter;
        document.querySelectorAll('[data-community-filter]').forEach(item => item.classList.toggle('active', item === button));
        renderCommunityPosts();
    }));
    document.getElementById('communityPosts')?.addEventListener('click', event => {
        const button = event.target.closest('[data-contact-name]');
        if (button) showAlert('communityMessage', `Start a conversation with ${button.dataset.contactName} through your agreed project channel.`, 'info');
    });
    document.getElementById('communityPostForm')?.addEventListener('submit', event => {
        event.preventDefault();
        const user = getCurrentUser();
        if (!user) return showAlert('communityMessage', 'Create an account before publishing to the community.', 'error');
        const post = {
            author: user.username,
            role: document.getElementById('communityPostRole').value,
            type: document.getElementById('communityPostType').value,
            title: document.getElementById('communityPostTitle').value.trim(),
            body: document.getElementById('communityPostBody').value.trim(),
            contact: document.getElementById('communityPostContact').value.trim(),
            closedDeal: document.getElementById('communityPostClosed').checked,
            timestamp: 'Just now'
        };
        const posts = getCommunityPosts();
        posts.unshift(post);
        localStorage.setItem('hydroCommunityPosts', JSON.stringify(posts));
        event.target.reset();
        showAlert('communityMessage', 'Your post is now visible in the community feed.', 'success');
        renderCommunityPosts();
    });
}

function highlightActiveNav() {
    const currentPage = window.location.pathname.split('/').pop().toLowerCase() || 'index.html';
    document.querySelectorAll('.nav-links a').forEach(link => {
        const linkPage = (link.getAttribute('href') || '').split('/').pop().toLowerCase() || 'index.html';
        if (linkPage === currentPage) {
            link.classList.add('active-link');
        } else {
            link.classList.remove('active-link');
        }
    });
}

function triggerEmojiRain() {
    const layer = document.getElementById('emojiRainLayer');
    const main = document.querySelector('.page');
    if (!layer || !main) return;

    const emojis = ['✨', '🚀', '💡', '💬', '🎉', '🤝', '🌟', '🔥'];
    layer.innerHTML = '';
    main.style.opacity = '0';
    main.style.transform = 'translateY(14px)';
    main.style.transition = 'opacity 0.35s ease, transform 0.35s ease';

    window.setTimeout(() => {
        for (let i = 0; i < 22; i++) {
            const emoji = document.createElement('span');
            emoji.className = 'emoji-fall';
            emoji.textContent = emojis[i % emojis.length];
            emoji.style.left = `${(Math.random() * 100)}vw`;
            emoji.style.animationDelay = `${(Math.random() * 0.7).toFixed(2)}s`;
            emoji.style.animationDuration = `${(2.2 + Math.random() * 1.7).toFixed(2)}s`;
            emoji.style.setProperty('--drift', `${(Math.random() * 120 - 60).toFixed(0)}px`);
            emoji.style.fontSize = `${(1.3 + Math.random() * 1.7).toFixed(2)}rem`;
            layer.appendChild(emoji);
        }
    }, 180);

    window.setTimeout(() => {
        main.style.opacity = '1';
        main.style.transform = 'translateY(0)';
    }, 1200);

    window.setTimeout(() => {
        layer.innerHTML = '';
    }, 4200);
}

function initPage() {
    showNavUser();
    const page = document.body.dataset.page;
    if (!page) return;
    switch (page) {
        case 'login':
            switchAuthTab('login');
            document.getElementById('loginForm')?.addEventListener('submit', handleLoginForm);
            document.getElementById('registerForm')?.addEventListener('submit', handleRegisterForm);
            document.getElementById('loginTab')?.addEventListener('click', () => switchAuthTab('login'));
            document.getElementById('registerTab')?.addEventListener('click', () => switchAuthTab('register'));
            break;
        case 'feed':
            triggerEmojiRain();
            renderFeedPage();
            initSocialFeedActions();
            document.getElementById('feedForm')?.addEventListener('submit', handleFeedPost);
            break;
        case 'dashboard':
            renderDashboardPage();
            break;
        case 'account':
            renderAccountPage();
            break;
        case 'settings':
            initSettingsPage();
            initSettingsOptions();
            document.getElementById('settingsForm')?.addEventListener('submit', handleSettingsSubmit);
            break;
        case 'inventor':
            initInventorPage();
            break;
        case 'inventor-community':
            initCommunityPage();
            break;
        default:
            break;
    }
}

window.addEventListener('DOMContentLoaded', () => {
    applyDarkMode();
    initPage();
    highlightActiveNav();
    updateHeaderActionVisibility();
    window.addEventListener('scroll', updateHeaderActionVisibility, { passive: true });
});
