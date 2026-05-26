<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>Companies & Advertisements - HYDROBOTICS</title>
	<link rel="stylesheet" href="css/theme.css?v=2">
	<style>
		* { margin: 0; padding: 0; box-sizing: border-box; }
		body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f3f6fb; color: #11263b; }
		nav { background: #162b46; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
		.logo { color: #6ee7b7; font-weight: 700; font-size: 1.45rem; }
		nav ul { display: flex; gap: 1rem; list-style: none; }
		nav a { color: #eff6ff; text-decoration: none; }
		.hero { padding: 2rem; text-align: center; background: linear-gradient(135deg,#132e51,#26416e); color: white; }
		.hero h1 { font-size: 2.25rem; margin-bottom: 0.5rem; }
		.hero p { opacity: 0.8; max-width: 720px; margin: 0 auto; }
		.content { max-width: 1180px; margin: 2rem auto; padding: 0 1rem; }
		.grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(280px,1fr)); gap: 1.2rem; }
		.company-card, .ad-card { background: white; border-radius: 16px; padding: 1.35rem; box-shadow: 0 16px 40px rgba(17,38,59,0.08); }
		.company-card h2, .ad-card h2 { margin-bottom: 0.8rem; color: #0f2540; }
		.company-card p, .ad-card p { color: #475569; line-height: 1.7; }
		.company-since { display: block; margin-top: 0.85rem; color: #64748b; }
		.account-form { margin-top: 1rem; border-top: 1px solid #e2e8f0; padding-top: 1rem; }
		.account-form input, .account-form textarea, .account-form select { width: 100%; padding: 0.85rem 1rem; border-radius: 10px; border: 1px solid #cbd5e1; margin-top: 0.7rem; }
		.account-form button { margin-top: 1rem; padding: 0.95rem 1.2rem; border: none; border-radius: 10px; background: #10b981; color: white; cursor: pointer; font-weight: 700; }
		.account-form button:hover { opacity: 0.95; }
	</style>
</head>
<body>
	<nav>
		<div class="logo">HYDROBOTICS Companies</div>
		<ul>
			<li><a href="HOMEPAGE.PHP">Home</a></li>
			<li><a href="inventor.php">Inventor</a></li>
			<li><a href="feed.php">Public Feed</a></li>
			<li><a href="admin.php">Admin</a></li>
			<li><a href="hydrobotics.php">HYDROBOTICS</a></li>
		</ul>
	</nav>
	<section class="hero">
		<h1>Company Accounts & Advertisement Hub</h1>
		<p>Companies can access this area to manage their account, create sponsored messages, and share listings in the HYDROBOTICS eco-system.</p>
	</section>
	<main class="content">
		<div class="grid">
			<div class="company-card">
				<h2>BlueLift Logistics</h2>
				<p>Company account active for shipping and freight technology collaborations.</p>
				<span class="company-since">Since 2024</span>
			</div>
			<div class="company-card">
				<h2>EarthGear Systems</h2>
				<p>Advertises automation solutions for construction and field operations.</p>
				<span class="company-since">Since 2023</span>
			</div>
			<div class="company-card">
				<h2>HydroTech Partners</h2>
				<p>Shows company-sponsored content relevant to infrastructure innovation.</p>
				<span class="company-since">Since 2022</span>
			</div>
		</div>
		<div class="ad-card" style="margin-top: 1.5rem;">
			<h2>Post a new advertisement</h2>
			<form class="account-form" onsubmit="createAd(event)">
				<label>Advertisement Title</label>
				<input id="adTitle" type="text" placeholder="Smart fleet management" required>
				<label>Ad Description</label>
				<textarea id="adDescription" placeholder="Describe the offer or product for inventors and partners." required></textarea>
				<label>Target audience</label>
				<select id="adTarget">
					<option value="inventors">Inventors</option>
					<option value="companies">Companies</option>
					<option value="public">Public viewers</option>
				</select>
				<button type="submit">Publish Advertisement</button>
			</form>
		</div>
	</main>
	<script>
		function createAd(event) {
			event.preventDefault();
			alert('Mock publish: ad would be stored in a real system.');
		}
	</script>
<?php include 'ai_chatbot_widget.php'; ?>
</body>
</html>
