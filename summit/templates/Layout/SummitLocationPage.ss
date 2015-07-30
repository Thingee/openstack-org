<% if CityIntro %>
<div class="white city-intro">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                $CityIntro
            </div>
        </div>
    </div>
</div>
<% end_if %>
<div class="light city-nav city" id="nav-bar">
    <div class="container">
        <ul class="city-nav-list">
            <li>
                <a href="#venue">
                    <i class="fa fa-map-marker"></i>
                    Venue
                </a>
            </li>
            <li>
                <a href="#hotels">
                    <i class="fa fa-h-square"></i>
                    Hotels &amp; Airport
                </a>
            </li>
            <% if GettingAround  %>
            <li>
                <a href="#getting-around">
                    <i class="fa fa-road"></i>
                    Getting Around
                </a>
            </li>
            <% end_if %>
            <% if TravelSupport  %>
                <li>
                    <a href="#travel-support">
                        <i class="fa fa-globe"></i>
                        Travel Support Program
                    </a>
                </li>
            <% end_if %>
            <% if VisaInformation  %>
            <li>
                <a href="#visa">
                    <i class="fa fa-plane"></i>
                    Visa Info
                </a>
            </li>
            <% end_if %>
            <% if Locals  %>
            <li>
                <a href="#locals">
                    <i class="fa fa-heart"></i>
                    Locals
                </a>
            </li>
            <% end_if %>
        </ul>
    </div>
</div>
<% loop $Venues %>
    <div id="venue">
        <div class="venue-row" style="background: rgba(0, 0, 0, 0) url('{$Top.VenueBackgroundImageUrl}') no-repeat scroll left top / cover ;">
            <div class="container">
                <h1>$Top.VenueTitleText</h1>
                <p>
                    <strong>$Name</strong>
                    $Address
                </p>
            </div>
        </div>
        <a href="{$Top.VenueBackgroundImageHeroSource}" class="photo-credit" data-toggle="tooltip" data-placement="left" title="{$Top.VenueBackgroundImageHero}" target="_blank">
            <i class="fa fa-info-circle"></i>
        </a>
    </div>
<% end_loop %>
<div class="white hotels-row" id="hotels">
<div class="venue-map" id="map-canvas"></div>
<div class="container">
    <div class="row">
        <div class="col-lg-8 col-lg-push-2">
            <h1>Hotels & Airport</h1>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-8 col-lg-push-2">
            <h5 class="section-title">Official Summit Hotels</h5>
            <p style="margin-bottom:30px;">
                <i class="fa fa-hotel fa-4x"></i>
            </p>
            $LocationsTextHeader
        </div>
    </div>
    <% loop Hotels %>
        <% if $First() %>
        <div class="row">
        <% end_if %>
        <div class="col-lg-4 col-md-4 col-sm-4 hotel-block">
            <h3>{$Pos}. $Name</h3>
            <p>
                $Address
            </p>
            <p<% if $IsSoldOut %> class="sold-out-hotel" <% end_if%>>
                <% if $IsSoldOut %>
                    SOLD OUT
                <% else %>
                    <a href="$Top.Link#map-canvas" class="marker-link"  data-location-id="{$ID}"  alt="View On Map"><i
                            class="fa fa-map-marker"></i> Map</a>
                    <% if $BookingLink %>
                        <a href="{$BookingLink}" target="_blank" alt="Visit Bookings Site"><i class="fa fa-home"></i>
                            Bookings</a>
                    <% else %>
                        <a href="{$WebsiteUrl}"><i class="fa fa-home"></i> Website</a>
                    <% end_if %>
                <% end_if %>
            </p>
        </div>
        <% if Last() %>
        </div>
        <% else_if $MultipleOf(3) %>
        </div>
        <div class="row">
        <% end_if %>
    <% end_loop %>
    <% if $Airports %>
        <% if $AirportsTitle %>
            <div class="row">
                <div class="col-lg-8 col-lg-push-2">
                    <h5 class="section-title">$AirportsTitle</h5>
                    <p>
                        $AirportsSubTitle
                    </p>
                </div>
            </div>
        <% end_if %>
        <div class="row">

        <% loop Airports %>

                <div class="col-sm-4 col-sm-push-2 airport-block">
                    <h3>$Name</h3>
                    <p>
                        $Address
                    </p>
                    <p>
                        <a class="marker-link" href="$Top.Link#map-canvas" data-location-id="{$ID}" alt="View On Map"><i
                                class="fa fa-map-marker"></i> Map</a>
                        <a href="{$WebsiteUrl}" target="_blank" alt="Visit Website"><i class="fa fa-home"></i> Website</a>
                    </p>
                </div>

        <% end_loop %>
    </div>
    <% end_if %>
    <% if OtherLocations  %>
    <div class="row">
        <div class="col-lg-8 col-lg-push-2 other-hotel-options">
            <h5 class="section-title">House Sharing</h5>
            $OtherLocations
        </div>
    </div>
    <% end_if %>
</div>
</div>
<% if GettingAround  %>
<div class="blue" id="getting-around">
    <div class="container">
        $GettingAround
    </div>
</div>
<% end_if %>
<% if TravelSupport  %>
    <div class="light" id="travel-support">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 col-lg-push-2">
                    $TravelSupport
                </div>
            </div>
        </div>
    </div>
<% end_if %>
<% if VisaInformation  %>
<div class="white visa-row" id="visa">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 col-lg-push-2">
                $VisaInformation
            </div>
        </div>
    </div>
</div>
<% end_if %>
<% if Locals %>
<div class="white locals-row" id="locals">
    <div class="container">
        $Locals
    </div>
</div>
<% end_if %>
<% if AboutTheCity %>
<div class="about-city-row" style="background: rgba(0, 0, 0, 0) url('{$AboutTheCityBackgroundImageUrl}') no-repeat scroll left top / cover ">
    $AboutTheCity
    <p>
        <% if $Summit.RegistrationLink %>
            <a href="$Summit.RegistrationLink" class="btn register-btn-lrg">Register Now</a>
        <% end_if %>
    </p>
    <a href="{$AboutTheCityBackgroundImageHeroSource}" class="photo-credit" data-toggle="tooltip" data-placement="left" title="{$AboutTheCityBackgroundImageHero}" target="_blank"><i class="fa fa-info-circle"></i></a>
</div>
<% end_if %>
