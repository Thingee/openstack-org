<div class="container">
    <h1>$Event.Title</h1>
    <hr>
    When: $Event.StartDate <br>
    Where: $Event.Location.getFullName() <br>
    Capacity: $Event.Location.Capacity <br>
    Audience: $Event.AllowedSummitTypes.Audience <br>
    Description: $Event.Description<br>
    <hr>
    <% if $Event.Type.Type = 'Presentation' %>
        <div class="item-list row">
            <% loop Event.Materials() %>
                <% if First %>
                    <div class="col-sm-6">
                <% end_if %>
                <div class="item">
                    <% if ClassName = 'PresentationSlide' %>
                        <div class="slide" title="$Name">
                            <a href="$Link" target="_blank">$Name</a>
                        </div>
                    <% else %>
                        <div class="video">
                            <a href="https://youtube.googleapis.com/v/$YouTubeID" rel="shadowbox">$Name</a>
                        </div>
                    <% end_if %>
                </div>
                <% if Mid %>
                    </div><div class="col-sm-6">
                <% end_if %>
                <% if Last %>
                    </div>
                <% end_if %>
            <% end_loop %>
        </div>
        <hr>
    <% end_if %>
    <% if Event.getSpeakers().toArray() %>
        Speakers: <br>
        <% loop Event.getSpeakers() %>
            <div class="row">
                <div class="speaker_pic col-md-2"> $ProfilePhoto(50) </div>
                <div class="speaker_profile col-md-6">
                    <div> $Title $FirstName $LastName </div>
                    <div> $CurrentAffiliation().Role, $CurrentAffiliation().Org().Name </div>
                    <div> $Bio </div>
                </div>
            </div>
        <% end_loop %>
        <hr>
    <% end_if %>


    <% if Event.Attendees() %>
        Attendees ($Event.Atendees.TotalItems): <br>
        <% loop Event.Attendees() %>
            <div class="attendee_pic col-md-2"> $Member.ProfilePhoto(50) </div>
        <% end_loop %>
    <% end_if %>
</div>
