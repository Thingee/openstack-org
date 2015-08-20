
require('./approve-modal.tag')
require('./change-error-modal.tag')


<change-requests>

	<approve-modal request="{ activeRequest }" />
	<change-error-modal request="{ activeRequest }" />


	<h1>Change Requests</h1>

	<table class="table">
		<tr>
			<th>Presentation</th>
			<th>Status</th>
			<th>Old Category</th>
			<th></th>
			<th>New Category</th>
			<th>Requester</th>
			<th></th>
		</tr>
		<tr each="{request in requests}" class="{ completed:  request.done == '1' } { selected:  request.has_selections == true }">
			<td>
				<a href="#" data-toggle="modal" data-target="#approveModal" onclick="{ parent.setRequest(request) }"  if="{ !request.has_selections }">{ request.presentation_title }</a>
				<a href="#" data-toggle="modal" data-target="#changeErrorModal" onclick="{ parent.setRequest(request) }" if="{ request.has_selections }">{ request.presentation_title }</a></td>				
			<td>
				<span if="{ request.done == '1'}">Completed</span>
				<span if="{ request.done == '0' }">Requested</span>
			</td>
			<td>{ request.old_category.title }</td>
			<td><i class="fa fa-long-arrow-right"></i></td>
			<td>{ request.new_category.title }</td>
			<td>{ request.requester }</td>
		</tr>
	</table>

	<style scoped>
		.completed { opacity: 0.4;}
		.selected, .selected a { color: red;}
	</style>

	<script>

		self = this
		self.requests = []
		self.activeRequest = []

		this.on('mount', function(){
			opts.api.trigger('load-change-requests')
		})

		setRequest(request) {
			return function(e) {
				self.activeRequest = request
				self.update()
			}
		}

		opts.api.on('change-requests-loaded', function(response){
			self.requests = []
			self.update()
			self.requests = response
			self.update()
		})

	</script>


</change-requests>