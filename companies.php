<?php
$page_title = 'Companies & Advertisements - HYDROBOTICS';
include 'header.php';
?>

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
<?php include 'footer.php'; ?>

