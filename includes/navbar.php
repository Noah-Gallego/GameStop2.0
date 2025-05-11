<?php
/* navbar.php – GameStop 2.0 reusable nav */
$current = basename($_SERVER['PHP_SELF'], '.php');
$logged  = isset($_SESSION['uid'], $_SESSION['username']);
$admin = isset($_SESSION['admin']);
?>

<nav class="navbar">
  <div class="container">
    <div class="row-nav justify-content-between align-items-center">

      <!-- logo -->
      <?php if ($logged): ?>
        <a class="logo" href="dashboard.php">GameStop<span>2.0</span></a>
      <?php else: ?>
        <div class="logo">GameStop<span>2.0</span></div>
      <?php endif; ?>

      <!-- desktop search opener (hidden on ≤768 px) -->
      <?php if ($logged): ?>
        <input id="nav-search-opener"
               class="nav-search-opener"
               type="text"
               placeholder="Search…"
               readonly>

      <!-- mobile search button (visible on ≤768 px) -->
        <button id="mobile-search-btn" class="search-btn">
          <i class="material-icons">search</i>
        </button>
        <?php endif; ?>

      <!-- hamburger -->
      <input type="checkbox" id="click">
      <label for="click" class="menu-btn">
        <i class="material-icons">menu</i>
      </label>

            <!-- links -->
      <ul>
        <?php if ($logged): ?>

        <!-- Dropdown wrapper -->
        <li class="nav-item dropdown <?= in_array($current,
             ['dashboard','inventory','trades','wishlist']) ? 'active':'' ?>">

          <!-- button that the user clicks -->
          <a href="#" class="drop-toggle">
            <span>Home</span> <i class="material-icons arrow">expand_more</i>
          </a>

          <!-- hidden menu -->
          <ul class="submenu">
            <li>
              <a href="dashboard.php"
                class="<?= $current=='dashboard'?'active':'' ?>">Dashboard
              </a>
            </li>
            <li>
              <a href="inventory.php"
                 class="<?= $current=='inventory'?'active':'' ?>">Inventory
              </a>
            </li>
            <li>
              <a href="trades.php"
                class="<?= $current=='trades'?'active':'' ?>">Trades
              </a>
            </li>
            <?php if ($admin): ?>
              <!-- Only shows if current user is an admin: CR -->
            <li>
              <a href="admin_trades.php"
                  class="<?= $current=='admin_trades'?'active':'' ?>">(A)Trades
              </a>
            </li>
            <?php endif; ?>

            <li>
              <a href="wishlist.php"
                   class="<?= $current=='wishlist'?'active':'' ?>">Wishlist
              </a>
            </li>
          </ul>
        </li>

        <!-- top‑level links now -->
        <li><a href="users.php"
               class="<?= $current==='users'?'active':'' ?>">Users</a></li>
        <li><a href="games.php"
               class="<?= $current==='games'?'active':'' ?>">Games</a></li>
        <?php endif; ?>

        <!-- theme toggle -->
        <li>
          <button id="theme-switch" class="btn theme-toggle-btn" title="Switch theme">
            <i class="material-icons">brightness_6</i>
          </button>
        </li>
      </ul>

    </div>
  </div>
</nav>

<?php /* -------------- full-screen overlay -------------- */ ?>
<?php if ($logged): ?>
<div id="search-overlay" class="search-overlay">
  <div class="overlay-box">
    <button id="overlay-close" class="overlay-close" title="Close">✕</button>

    <input id="overlay-search-input"
           class="overlay-search-input"
           placeholder="Search for users or games…"
           autocomplete="off">

    <div id="overlay-search-results"></div>
  </div>
</div>
<?php endif; ?>

<?php if ($logged): ?>
<script>
  // Wait until the page’s HTML is fully loaded
  document.addEventListener('DOMContentLoaded', () => {

    /* === 1. Grab important elements by their IDs === */
    const overlay  = document.getElementById('search-overlay');       // full-screen container
    const openers  = [                                                // buttons that open overlay
      document.getElementById('nav-search-opener'),
      document.getElementById('mobile-search-btn')
    ].filter(Boolean);                                                // remove any that aren’t on the page

    const closeBtn = document.getElementById('overlay-close');        // “×” button inside
    const input    = document.getElementById('overlay-search-input'); // the text input
    const results  = document.getElementById('overlay-search-results'); // where we’ll show matches
    const MAX      = 15;                                              // items before “Show all…”

    /* === 2. Show & hide the overlay === */
    function openOverlay() {
      overlay.classList.add('open');      // add CSS class to make it visible
      input.value      = '';              // clear previous query
      results.innerHTML= '';              // clear previous results
      input.focus();                      // put cursor in input box
      document.body.style.overflow = 'hidden'; // prevent background scrolling
    }

    function closeOverlay() {
      overlay.classList.remove('open');   // hide it
      document.body.style.overflow = '';  // restore page scroll

      // ── Reset every section so it’s expanded next time
      document.querySelectorAll(
        '#overlay-search-results .srch-header ul'
      ).forEach(ul => ul.style.display = 'block');
    }

    // Attach open/close handlers
    openers.forEach(btn => btn.addEventListener('click', openOverlay));
    closeBtn.addEventListener('click', closeOverlay);
    overlay.addEventListener('click', e => {
      // click outside the white box also closes
      if (e.target === overlay) closeOverlay();
    });

    /* === 3. Live search on each keystroke === */
    input.addEventListener('input', () => {
      const q = input.value.trim();      // remove leading/trailing spaces
      if (!q) { 
        results.innerHTML = '';          // if empty, clear results
        return;
      }

      // send POST request to our PHP search endpoint
      fetch('includes/search.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'search_data=' + encodeURIComponent(q)
      })
      .then(r => r.json())               // parse JSON from server
      .then(({ users, games, error }) => {
        if (error) throw new Error(error);

        // build HTML for each category
        let accounts = buildSection('Users', users, u => {
            const banIcon = <?= $admin ? "'<i class=\"material-icons\" style=\"color: #dc3545; cursor: pointer; margin-left: 8px; vertical-align: middle; transition: color 0.2s ease;\" title=\"Ban User\" data-uid=\"' + u.UID + '\">gavel</i>'" : "''" ?>;
            return `<li>👤 <a href="profile.php?uid=${u.UID}">${u.name}</a> ${banIcon}</li>`;
        });
        let vgames    = buildSection('Games', games, g => {
          // show thumbnail if there’s an image URL
          const img = g.image?.trim()
                    ? `<img src="${g.image}" width="32" height="32" alt="">`
                    : `<svg width="800px" height="800px" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
    <path d="m 4 1 c -1.644531 0 -3 1.355469 -3 3 v 1 h 1 v -1 c 0 -1.109375 0.890625 -2 2 -2 h 1 v -1 z m 2 0 v 1 h 4 v -1 z m 5 0 v 1 h 1 c 1.109375 0 2 0.890625 2 2 v 1 h 1 v -1 c 0 -1.644531 -1.355469 -3 -3 -3 z m -5 4 c -0.550781 0 -1 0.449219 -1 1 s 0.449219 1 1 1 s 1 -0.449219 1 -1 s -0.449219 -1 -1 -1 z m -5 1 v 4 h 1 v -4 z m 13 0 v 4 h 1 v -4 z m -4.5 2 l -2 2 l -1.5 -1 l -2 2 v 0.5 c 0 0.5 0.5 0.5 0.5 0.5 h 7 s 0.472656 -0.035156 0.5 -0.5 v -1 z m -8.5 3 v 1 c 0 1.644531 1.355469 3 3 3 h 1 v -1 h -1 c -1.109375 0 -2 -0.890625 -2 -2 v -1 z m 13 0 v 1 c 0 1.109375 -0.890625 2 -2 2 h -1 v 1 h 1 c 1.644531 0 3 -1.355469 3 -3 v -1 z m -8 3 v 1 h 4 v -1 z m 0 0" fill="#2e3434" fill-opacity="0.34902"/>
</svg>`; //<-   default
          return `<li>${img}<a href="games.php?gid=${g.GID}&title=${g.title}">${g.title}</a></li>`;
        });

        // insert results or a “no results” message
        results.innerHTML = accounts + vgames || '<p class="srch-none">No results.</p>';
      })
      .catch(() => {
        // network or server error
        results.innerHTML = '<p class="srch-error">Error loading results</p>';
      });
    });

    /* === 4. Build a collapsible section for Users/Games === */
    function buildSection(label, arr, rowTpl) {
      if (!arr.length) return '';       // skip if no items

      // full HTML for all items (used when expanding)
      const fullRows = arr.map(rowTpl).join('');
      // snippet for the first MAX items
      const snippet  = arr.slice(0, MAX).map(rowTpl).join('');
      // “Show all…” link if there are more than MAX
      const moreLink = arr.length > MAX
                     ? '<li class="srch-more">Show all results…</li>'
                     : '';
      // escape single-quotes so data-attribute stays valid
      const fullEsc  = fullRows.replace(/'/g, '&#39;');

      return `
        <div class="srch-header" data-open="true">
          <span class="srch-label">${label} (${arr.length})</span>
          <ul data-full='${fullEsc}' style="display:block;">
            ${snippet}${moreLink}
          </ul>
        </div>`;
    }

    /* === 5. Expand full list when “Show all…” is clicked === */
    results.addEventListener('click', e => {
      if (e.target.classList.contains('srch-more')) {
        const ul = e.target.closest('ul');
        ul.innerHTML = ul.dataset.full;  // replace snippet with fullRows
      }
    });

    /* === 6. Toggle collapse/expand on section header click === */
    results.addEventListener('click', e => {
      // only run when the label is clicked
      if (e.target.classList.contains('srch-label')) {
        const header = e.target.closest('.srch-header');
        const list   = header.querySelector('ul');
        const isOpen = header.getAttribute('data-open') === 'true';

        // hide if open, show if closed
        list.style.display = isOpen ? 'none' : 'block';
        header.setAttribute('data-open', !isOpen);
      }
    });

    results.addEventListener('click', e => {
        if (e.target.matches('.material-icons[title="Ban User"]')) {
            const userId = e.target.getAttribute('data-uid');
            if (confirm(`Are you sure you want to ban user ID ${userId}?`)) {
                fetch('includes/ban_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'uid=' + encodeURIComponent(userId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('User banned successfully.');
                        e.target.style.color = '#000';
                        e.target.title = 'User Banned';
                        e.target.innerText = 'block';
                    } else {
                        alert('Failed to ban user.');
                    }
                })
                .catch(() => alert('Server error while banning user.'));
            }
        }
    });
    
    /* === Dropdown: toggle submenu on click === */
    document.querySelectorAll('.drop-toggle').forEach(btn => {
      
      btn.addEventListener('click', e => {
        e.preventDefault();                               // stop “#” navigation
        const li = btn.parentElement;                     // <li class="dropdown">
        li.classList.toggle('open');                      // show / hide submenu
        // close any other open dropdowns
        document.querySelectorAll('.navbar li.dropdown.open')
          .forEach(other => { if (other !== li) other.classList.remove('open'); });
        
        // Toggle Rotate Class
        const arrow = btn.querySelector('.arrow');
        arrow.classList.toggle('rotate');
        });
    });
    
    /* === Close dropdown when clicking anywhere else === */
    document.addEventListener('click', e => {
      // if the click target was *not* inside any dropdown...
      if (!e.target.closest('li.dropdown')) {
        document.querySelectorAll('li.dropdown.open')
          .forEach(li => li.classList.remove('open'));
      }
    });
  });  

  // end DOMContentLoaded
</script>
<style>
  .arrow {
    display: inline-block;
    transition: transform 0.3s ease;
    transform: rotate(0deg); /* default state */
  }
  .rotate {
    display: inline-block;
    transform: rotate(180deg);
    transition: transform 0.3s ease;
  }
</style>
<?php endif; ?>
