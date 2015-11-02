
<% require themedCSS(case-studies) %>
<% require themedCSS(shadowbox) %>
<% require themedCSS(bxslider) %>
<% require javascript(themes/openstack/javascript/users.js) %>
<% require javascript(themes/openstack/javascript/jquery.bxslider.min.js) %>


<h1>OpenStack Powers Demanding Production Workloads <strong>Worldwide</strong>.</h1>
</div>
<div class='case-studies-hero'>
	<div class='container'>
		<ul class='rotator_new rotator'>
		<li data-label = 'Enterprise Booklet' class = 'enterprise-booklet'>
			<p class='largeQuote'>Current OpenStack users wrote a booklet of use cases and planning tips with IT executives in mind</p>
			<a href="//www.openstack.org/assets/pdf-downloads/business-perspectives.pdf">Download the booklet</a>
		</li>
		<li data-label = 'Automotive' class = 'automotive'>
			<p class='largeQuote'>Top 10 Automaker Turning Customer Insights Into Action with OpenStack</p>
			<a href="/enterprise/auto/">Read The Case Study</a>
		</li>
		<li data-label = 'Virtualization integration' class = 'virtualization'>
			<p class='largeQuote'>Modernizing Virtualized Infrastructures with the OpenStack Cloud Management Platform</p>
			<a href="/enterprise/virtualization-integration/">Read The Case Study</a>
		</li>
		<li data-label = 'Workload portability' class = 'workload'>
			<p class='largeQuote'>Expediting Digital Workflow with OpenStack</p>
			<a href="/enterprise/workload-portability/">Read The Case Study</a>
		</li>

		<% loop Slider %>
			<% if Type == Quote %>
			<li data-label='$SlideLabel'>
				<p class='largeQuote'>&ldquo;$Quote&rdquo;</p>
				<p class='attribution'>&mdash; $Author</p>
			<% else %>
			<li data-label='$UserStoriesTopics.Topic'>
				<% loop getStories %>
					<% if Type == video %>
						<% if VideoURL %>
						<a href="https://youtube.googleapis.com/v/$VideoURL" rel='shadowbox'>
						<% else %>
						<a href="https://youtube.googleapis.com/v/$UserStory.YouTubeID" rel='shadowbox'>
						<% end_if %>
					<% else %>
					<a href='$UserStory.Link'>
					<% end_if %>
					$Thumbnail305
					</a>
				<% end_loop  %>
			<% end_if %>
                <% if ButtonText && ButtonLink %>
                <a class="slide-button" href="$ButtonLink">$ButtonText</a>
			    <% end_if %>
            </li>
		<% end_loop %>
		</ul>
	</div>
</div>
<div class='container'>
	<div class="row">
		<div class='col-lg-6'>
			<h3>OpenStack Users By Industry</h3>
		</div>
		<div class='col-lg-6' style="text-align:right">
			Would you like to be listed here? &nbsp;
			<a class='roundedButton' href='/user-survey/'>Add Your Company</a>
		</div>
	</div>

	<div class='last deploymentTagGroup'>
		<% loop Industries %>
			<a class='categoryTag' href='{$Top.URLSegment}/#$IndustryName'>
				$IndustryName ($Stories.Count)
			</a>
		<% end_loop %>
	</div>

<% loop Industries %>
	<a name='$IndustryName'></a>
	<h4>$IndustryName</h4>

	<div class='span-24 last'>
		<div class='span-6 featuredLogo'>
				$FeaturedStory.UserStory.SummaryImg220
		</div>
		<div class='span-18 last categoryDescription'>
			$FeaturedStory.UserStory.Content
		</div>
	</div>

	<table class='deployments'>
		<tr>
			<th class='name'>Name</th>
			<th class='country'>Country</th>
			<th class='type'>Type</th>
			<th class='extras'>Case Study</th>
			<th class='extras'>Video</th>
		</tr>
		<% loop Stories %>
		<tr>
			<td>
				$Title
			</td>
			<td>
				$Country
			</td>
			<td>
				$DeploymentType
			</td>
			<td>
				<% if ShowCaseStudy %>
					<a class='case-study' href='$Link'><img src='{$ThemeDir}/images/user-stories-page/casestudy.png' />Case Study</a>
				<% end_if %>
			</td>
			<td>
				<% if ShowVideo %>
					<a class='video-link' href='https://youtube.googleapis.com/v/$YouTubeID' rel='shadowbox'><img src='{$ThemeDir}/images/user-stories-page/videolink.png' />Video</a>
				<% end_if %>
			</td>
		</tr>
		<% end_loop %>
	</table>

<% end_loop  %>
</div>
