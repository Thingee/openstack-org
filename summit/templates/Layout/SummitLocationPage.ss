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
            <a href="{$Top.VenueBackgroundImageHeroSource}" class="photo-credit" data-toggle="tooltip" data-placement="left" title="{$Top.VenueBackgroundImageHero}" target="_blank">
                <i class="fa fa-info-circle"></i>
            </a>
        </div>
    </div>
<% end_loop %>
<div class="white hotels-row" id="hotels">

<% if $CampusGraphic %>

    <div class="venue-map-drawn">
        <div class="container">
            <div class="row">
                <div class="col-sm-8 col-sm-push-2">
                    <h5 class="section-title">Summit Campus</h5>
                </div>
            </div>
        </div>
        <img class="" src="{$CampusGraphic}.svg" onerror="this.onerror=null; this.src={$CampusGraphic}.png" alt="OpenStack Summit Tokyo Hotels">
    </div>

<% else %>

    <div class="venue-map" id="map-canvas"></div>

<% end_if %>


<div class="container">
    <div class="row">
        <div class="col-lg-8 col-lg-push-2">
            <h5 class="section-title">Official Summit Hotels</h5>
            <p style="margin-bottom:30px;">
                <i class="fa fa-hotel fa-4x"></i>
            </p>
            $LocationsTextHeader

            <div class="alert alert-danger" role="alert">
                IMPORTANT: You must use the following promo code to receive the Summit discounted rate.<br>
                Please <a href="//openstack.org/assets/pdf-downloads/OS-Tokyo-Hotel-Info-Packet.pdf" target="_blank">read these instructions</a> for room info and additional details.<br><strong style="font-size:1.5em;">Promo Code: OST2015</strong>
            </div>
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

            <% if $LocationMessage %>
                <p>
                    $LocationMessage
                </p>
            <% end_if %>

            <p<% if $IsSoldOut %> class="sold-out-hotel" <% end_if%>>
                <% if $IsSoldOut %>
                    SOLD OUT
                <% else %> 

                    <% if not $Top.CampusGraphic %>
                        <a href="$Top.Link#map-canvas" class="marker-link"  data-location-id="{$ID}"  alt="View On Map"><i class="fa fa-map-marker"></i> Map</a>
                    <% end_if %>

                    <% if $DetailsPage %>
                        <a href="{$Top.Link}details/$ID" alt="Visit Bookings Site"><i class="fa fa-home"></i>
                            Booking Info</a>
                    <% else_if $BookingLink %>
                        <a href="{$BookingLink}" target="_blank" alt="Visit Bookings Site"><i class="fa fa-home"></i>
                            Bookings</a>
                    <% else %>
                        <a href="{$WebsiteUrl}"  data-toggle="modal" data-target="#Hotel{$ID}"><i class="fa fa-home"></i> Website</a>

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

    <!-- Hotel Website Modal -->
    <div class="modal fade" id="Hotel{$ID}">
      <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <h4 class="modal-title">Important</h4>
            </div>
            <div class="modal-body">
                <p class="center">
                    <i class="fa fa-exclamation-triangle fa-4x" style="color:#DA422F;"></i>
                </p>
                <p>
                    You must use the following promo code to receive the Summit discounted rate.
                    Please <a href="//openstack.org/assets/pdf-downloads/OS-Tokyo-Hotel-Info-Packet.pdf" target="_blank">read these instructions</a> for room info and additional details.
                </p>
                <p>
                    <strong style="font-size:1.5em;">Promo Code: OST2015</strong>
                </p>
            </div>
            <div class="modal-footer">
                <p style="text-align: center;">
                    <a href="{$Website}" class="hotel-alert-btn" target="_blank">Book your hotel room(s)</a>
                </p>
            </div>
        </div><!-- /.modal-content -->
      </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    <!-- End Hotel Website Modal -->

    <% end_loop %>
    <div class="row">
        <div class="col-sm-10 col-sm-push-1">
            <h5 class="section-title">More Hotel Details</h5>
            <div class="more-hotel-details">
                <p>
                    <i class="fa fa-users fa-2x"></i>
                </p>
                <p>
                    Booking for 10 or more rooms for the Summit?
                </p>
                <p>
                    Contact <a href="mailto:sarah@fntech.com">sarah@fntech.com</a>
                </p>
                <hr>
                <p>
                    <i class="fa fa-hotel fa-2x"></i>
                </p>
                <p>
                    Looking for additional hotels near the Summit venue?
                </p>
                <p>
                    <a href="#" data-toggle="modal" data-target="#otherHotelsModal">List of nearby hotels</a>
                </p>
            </div>
        </div>
    </div>
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
                <h1>Visa Information</h1>
                <div class="visa-steps-wrapper">
                    <h3>Get your Tokyo Summit Visa in 5 Steps</h3>
                    <h5><i class="fa fa-exclamation-circle"></i>The entire visa process can take up to 5 weeks, so <strong>apply now</strong>.</h5>
                    <div class="visa-steps-row">
                        <div class="steps-count">
                            <div class="visa-step">
                                <img src="/themes/openstack/images/summit/tokyo/visa-steps/1.png" alt="">
                                <p>
                                    Start now: Book your hotel, plane ticket &amp; summit registration
                                </p>
                            </div>
                            <div class="visa-step">
                                <img src="/themes/openstack/images/summit/tokyo/visa-steps/2.png" alt="">
                                <p>
                                    Complete the <a href="https://openstack.formstack.com/forms/visa_request_form" target="_blank">visa request form here</a>
                                </p>
                            </div>
                            <div class="visa-step">
                                <img src="/themes/openstack/images/summit/tokyo/visa-steps/3.png" alt="">
                                <p>
                                    Receive your visa invitation documents in the mail
                                </p>
                            </div>
                            <div class="visa-step">
                                <img src="/themes/openstack/images/summit/tokyo/visa-steps/4.png" alt="">
                                <p>
                                    Apply for your visa at your Japanese embassy or consulate
                                </p>
                            </div>
                            <div class="visa-step">
                                <img src="/themes/openstack/images/summit/tokyo/visa-steps/5.png" alt="">
                                <p>
                                    Wait for your visa to be issued, wihch may take up to 10 business days and pict it up from the embassy.
                                </p>
                            </div>
                        </div>
                        <div class="visa-docs">
                            <h4>Bring these documents when you apply for your visa:</h4>
                            <ul>
                                <li>Valid passort.</li>
                                <li>Two 45mm x 45mm photos taken within the last 6 months.</li>
                                <li>Copies of hotel &amp; flight reservations from your travel agent.</li>
                                <li>Summit invitation.</li>
                                <li>Documentation showing permission to travel from your company.</li>
                            </ul>
                        </div>
                    </div>
                </div>

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

    <!-- Other Hotels Modal -->
    <div class="modal fade" id="otherHotelsModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
            <h4 class="modal-title">Additional Hotels</h4>
          </div>
          <div class="modal-body">
          <p class="center">
              <i class="fa fa-hotel fa-4x"></i>
          </p>
            <p class="center">
                Here is a list of additional hotels near the Summit venue in Tokyo. Take the train line shown to <strong>Shinagawa Station</strong>, across the street from the Summit venue.
            </p>
            <table class="table">
                <tr>
                    <td>Hotel</td>
                    <td>Distance</td>
                    <td>Train Line</td>
                </tr>
                <tr>
                    <td><a href="http://www.shinagawa.keikyu-exinn.co.jp/en/index.html" target="_blank">Keikyu EX Inn Shinagawa Ekimae</a></td>
                    <td>450m - 5 min walk</td>
                    <td>none</td>
                </tr>
                <tr>
                    <td><a href="http://www.intercontinental-strings.jp/eng/index.html" target="_blank">The Strings by InterContinental Tokyo</a></td>
                    <td>900m - 11 min walk</td>
                    <td>none</td>
                </tr>
                <tr>
                    <td><a href="http://www.westin-tokyo.co.jp/" target="_blank">The Westin Tokyo</a></td>
                    <td>11 min walk to Ebisu Station</td>
                    <td>Yamanote Line</td>
                </tr>
                <tr>
                    <td><a href="http://www.miyakohotels.ne.jp/tokyo/english/" target="_blank">Sheraton Miyako Hotel Tokyo</a></td>
                    <td>1.6km - 20 min walk</td>
                    <td>none</td>
                </tr>
                <tr>
                    <td><a href="http://www.tokyo-marriott.com/" target="_blank">Tokyo Marriott</a></td>
                    <td>9 min walk to Kitashinagawa Station</td>
                    <td>Keikyu Main Line</td>
                </tr>
                <tr>
                    <td><a href="http://www.shinagawa.keikyu-exinn.co.jp/en/index.html" target="_blank">Hotel Villa Fontaine Tamachi</a></td>
                    <td>10 min walk to Tamachi Station</td>
                    <td>Keihin Tohoku Line > Yamanote Lineto</td>
                </tr>
                <tr>
                    <td><a href="http://www.hvf.jp/eng/mita.php" target="_blank">Hotel JAL City Tamachi Tokyo</a></td>
                    <td>9 min walk to Tamachi Station</td>
                    <td>Keihin Tohoku Line > Yamanote Lineto</td>
                </tr>
                <tr>
                    <td><a href="http://tamachi.gracery.com/" target="_blank">Hotel Gracery Tamachi</a></td>
                    <td>9 min walk to Tamachi </td>
                    <td>Keihin Tohoku Line > Yamanote Lineto</td>
                </tr>
            </table>
          </div>
          <div class="modal-footer">
          </div>
        </div><!-- /.modal-content -->
      </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    <!-- End Other Hotels Modal -->