<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function return_view()
    {
        $title = "Upload XML files";

        return view('upload', [
            'title' => $title
        ]);
    }

    public function upload_files(Request $request)
    {

        // Get errors, if any, from loading files
        $err = [
            "air_err_code" => $request->file('air')->getError(),
            "air_err_message" => $request->file('air')->getErrorMessage(),
            "s_err_code" => $request->file('s')->getError(),
            "s_err_message" => $request->file('s')->getError(),
        ];

        // If any file loading error return details
        if ($err["air_err_code"] !== 0 || $err["s_err_code"] !== 0) {

            $response = [
                'success' => false,
                'error' => $err,
            ];

            return $response;
        }

        // Translate xml files to xml objects
        $air_file = $request->file('air');
        $s_file = $request->file('s');
        $air_xml = simplexml_load_file($air_file, null, null, "air", true);
        $s_xml = simplexml_load_file($s_file, null, null, "s", true)->children('s', true)->Body->children('');

        // Get information from xml objects

        // Get flight details
        $flight_details_list = $air_xml->FlightDetailsList->FlightDetails;

        foreach ($flight_details_list as $details) {
            $details_attr = $details->attributes();
            $flight_details_key = strval($details_attr['Key']);

            $flights_details[$flight_details_key] = [
                'Origin' => strval($details_attr['Origin']),
                'Destination' => strval($details_attr['Destination']),
                'DepartureTime' => strval($details_attr['DepartureTime']),
                'ArrivalTime' => strval($details_attr['ArrivalTime']),
                'FlightTime' => strval($details_attr['FlightTime']),
                'Equipment' => strval($details_attr['Equipment']),
                'OriginTerminal' => strval($details_attr['OriginTerminal']),
                'DestinationTerminal' => strval($details_attr['DestinationTerminal']),
            ];
        }

        // Get air segment details
        $air_segments_list = $air_xml->AirSegmentList->AirSegment;

        foreach ($air_segments_list as $segment) {
            $segment_attr = $segment->attributes();
            $segment_key = strval($segment_attr['Key']);
            $codeshare_info = $segment->CodeshareInfo;
            $flight_details_ref = $segment->FlightDetailsRef;

            if ($codeshare_info) {

                $codeshare_attr = $codeshare_info->attributes();

                if ($codeshare_attr['OperatingCarrier'] || $codeshare_attr['OperatingFlightNumber']) {
                    $has_codeshare_info = true;
                } else {
                    $has_codeshare_info = false;
                    $codeshare_attr = [
                        'OperatingCarrier' => '',
                        'OperatingFlightNumber' => '',
                    ];
                }
            } else {
                $has_codeshare_info = false;
                $codeshare_attr = [
                    'OperatingCarrier' => '',
                    'OperatingFlightNumber' => '',
                ];
            }

            $flight_details_ref_key = ($flight_details_ref) ? strval($flight_details_ref->attributes()['Key']) : '';



            $air_segments[$segment_key] = [
                'Carrier' => strval($segment_attr['Carrier']),
                'FlightNumber' => strval($segment_attr['FlightNumber']),
                'Origin' => strval($segment_attr['Origin']),
                'Destination' => strval($segment_attr['Destination']),
                'DepartureTime' => strval($segment_attr['DepartureTime']),
                'ArrivalTime' => strval($segment_attr['ArrivalTime']),
                'FlightTime' => strval($segment_attr['FlightTime']),
                'Equipment' => strval($segment_attr['Equipment']),
                'hasCodeshareInfo?' => $has_codeshare_info,
                'CodeshareInfo' => [
                    'OperatingCarrier' => strval($codeshare_attr['OperatingCarrier']),
                    'OperatingFlightNumber' => strval($codeshare_attr['OperatingFlightNumber']),

                ],
                'FlightDetails' => [
                    'FlightDetailsRefKey' => $flight_details_ref_key,
                    'FlightDetails' => $flights_details[$flight_details_ref_key],
                ],
            ];
        }

        // Get flights
        $pricing_solutions_list = $air_xml->AirPricingSolution;

        foreach ($pricing_solutions_list as $pricing_solution) {
            $pricing_solution_attr = $pricing_solution->attributes();

            $journeys_list = $pricing_solution->Journey;
            $booking_info_list = $pricing_solution->AirPricingInfo->BookingInfo;

            // Get journeys
            $journeys = [];
            foreach ($journeys_list as $journey_element) {
                $air_segment_refs = $journey_element->AirSegmentRef;
                $airlines = [];
                $journey_air_segments = [];

                // Get the codes from the airlines that operates the segments
                foreach ($air_segment_refs as $air_segment_ref) {

                    $air_segment_key = strval($air_segment_ref->attributes()['Key']);
                    $airline_code = $air_segments[$air_segment_key]['Carrier'];

                    $airlines[] = [
                        'code' => $airline_code,
                    ];

                    $journey_air_segments[] = $air_segments[$air_segment_key];
                }

                $unique_array = array_unique($airlines, SORT_REGULAR);

                // Get departure and arrival details
                $departure_air_segment = $journey_air_segments[0];
                $arrival_air_segment = end($journey_air_segments);
                $datetime_format_str = 'Y-m-d\TH:i:s.uP';
                $date_format_str = 'Y-m-d';
                $time_format_str = 'H:i';

                $departure_airport = $departure_air_segment['Origin'];
                $departure_datetime = date_create_from_format($datetime_format_str, $departure_air_segment['DepartureTime']);
                $departure_date = $departure_datetime->format($date_format_str);
                $departure_time = $departure_datetime->format($time_format_str);

                $departure = [
                    'airport' => ['code' => $departure_airport],
                    'date' => $departure_date,
                    'time' => $departure_time,
                ];

                $arrival_airport = $arrival_air_segment['Destination'];
                $arrival_datetime = date_create_from_format($datetime_format_str, $arrival_air_segment['ArrivalTime']);
                $arrival_date = $arrival_datetime->format($date_format_str);
                $arrival_time = $arrival_datetime->format($time_format_str);

                $arrival = [
                    'airport' => ['code' => $arrival_airport],
                    'date' => $arrival_date,
                    'time' => $arrival_time,
                ];

                // Get journey duration
                $travel_time = date_diff($arrival_datetime, $departure_datetime);
                $duration = [
                    'hours' => $travel_time->h,
                    'minutes' => $travel_time->i,
                ];


                $journeys[] = [
                    'journey' => '/* Id del trayecto */',
                    'airlines' => $unique_array,
                    'departure' => $departure,
                    'arrival' => $arrival,
                    'duration' => $duration,

                    'AirSegments' => $journey_air_segments,
                ];
            };

            $booking_info = [];
            foreach ($booking_info_list as $booking_info_element) {
                $booking_info_attr =  $booking_info_element->attributes();

                $booking_info[] = [
                    'BookingCode' => strval($booking_info_attr['BookingCode']),
                    'CabinClass' => strval($booking_info_attr['CabinClass']),
                    'FareInfoRef' => strval($booking_info_attr['FareInfoRef']),
                    'SegmentRef' => strval($booking_info_attr['SegmentRef']),
                ];
            }


            // $pricing_solutions[$pricing_solution_key] = [
            $flights[] = [
                'journeys' => $journeys,

                'TotalPrice' => strval($pricing_solution_attr['TotalPrice']),
                'BookingInfo' => $booking_info,
            ];
        }


        // Get air itineraries
        $air_itineraries_list = $s_xml->AirAvailSearchResponse->AirAvailSearchResult->AirAvail->AirItineraries->AirItinerary;

        $air_itineraries = [];
        foreach ($air_itineraries_list as $air_itinerary_element) {
            $itinerary_legs_list = $air_itinerary_element->AirItineraryLegs->AirItineraryLeg;

            $itinerary_legs = [];
            foreach ($itinerary_legs_list as $itinerary_legs_element) {
                $itinerary_legs[] = [
                    'DepartureDateTime' => trim(strval($itinerary_legs_element->DepartureDateTime)),
                    'ArrivalDateTime' => trim(strval($itinerary_legs_element->ArrivalDateTime)),
                    'ArrivalAirportLocationCode' => trim(strval($itinerary_legs_element->ArrivalAirportLocationCode)),
                    'ArrivalAirportTerminal' => trim(strval($itinerary_legs_element->ArrivalAirportTerminal)),
                    'DepartureAirportLocationCode' => trim(strval($itinerary_legs_element->DepartureAirportLocationCode)),
                    'DepartureAirportTerminal' => trim(strval($itinerary_legs_element->DepartureAirportTerminal)),
                    'FlightNumber' => trim(strval($itinerary_legs_element->FlightNumber)),
                    'OperatingCarrierCode' => trim(strval($itinerary_legs_element->OperatingCarrierCode)),
                    'MarketingCarrierCode' => trim(strval($itinerary_legs_element->MarketingCarrierCode)),
                    'AircraftType' => trim(strval($itinerary_legs_element->AircraftType)),
                ];
            }

            $air_itineraries[] = [
                'DepartureDateTime' => trim(strval($air_itinerary_element->DepartureDateTime)),
                'ArrivalDateTime' => trim(strval($air_itinerary_element->ArrivalDateTime)),
                'ArrivalAirportLocationCode' => trim(strval($air_itinerary_element->ArrivalAirportLocationCode)),
                'DepartureAirportLocationCode' => trim(strval($air_itinerary_element->DepartureAirportLocationCode)),
                'AirItineraryLegs' => $itinerary_legs,
                'TotalDuration' => trim(strval($air_itinerary_element->TotalDuration)),
            ];
        }


        // Get air pricing groups
        $air_pricing_groups_list = $s_xml->AirAvailSearchResponse->AirAvailSearchResult->AirAvail->AirPricingGroups->AirPricingGroup;

        foreach ($air_pricing_groups_list as $air_pricing_element) {

            $adult_ticket_amount = floatval($air_pricing_element->AdultTicketAmount);
            $children_ticket_amount = floatval($air_pricing_element->ChildrenTicketAmount);
            $infant_ticket_amount = floatval($air_pricing_element->InfantTicketAmount);
            $adult_tax_amount = floatval($air_pricing_element->AdultTaxAmount);
            $children_tax_amount = floatval($air_pricing_element->ChildrenTaxAmount);
            $infant_tax_amount = floatval($air_pricing_element->InfantTaxAmount);
            $agency_fee_amount = floatval($air_pricing_element->AgencyFeeAmount);
            $aramix_fee_amount = floatval($air_pricing_element->AramixFeeAmount);
            $discount_amount = floatval($air_pricing_element->DiscountAmount);
            $total = $adult_ticket_amount
                + $children_ticket_amount
                + $infant_ticket_amount
                + $adult_tax_amount
                + $children_tax_amount
                + $infant_tax_amount
                + $agency_fee_amount
                + $aramix_fee_amount
                + $discount_amount;


            $air_pricing_group_option_list = $air_pricing_element->AirPricingGroupOptions->AirPricingGroupOption;

            // Cycle through each pricing group option
            foreach ($air_pricing_group_option_list as $air_pricing_group_option_element) {

                $air_pricing_group_option_id = trim(strval($air_pricing_group_option_element->PricingGroupOptionID));
                $air_priced_itineraries_list = $air_pricing_group_option_element->AirPricedItineraries->AirPricedItinerary;

                // Cycle through each priced itinerary
                foreach ($air_priced_itineraries_list as $air_priced_itineraries_element) {
                    $itinerary_id = trim(strval($air_priced_itineraries_element->ItineraryID));

                    $air_priced_itinerary_legs_list = $air_priced_itineraries_element->AirPricedItineraryLegs->AirPricedItineraryLeg;

                    // Cycle through each priced itinerary leg
                    foreach ($air_priced_itinerary_legs_list as $air_priced_itinerary_legs_element) {
                        $air_priced_itinerary_legs[] = [
                            'CabinClass' => trim(strval($air_priced_itinerary_legs_element->CabinClass)),
                            'CabinType' => trim(strval($air_priced_itinerary_legs_element->CabinType)),
                        ];
                    }

                    $air_priced_itineraries[$itinerary_id] = [
                        'AirPricedItineraryLegs' => $air_priced_itinerary_legs,
                    ];
                }

                $air_pricing_group_options[$air_pricing_group_option_id] = [
                    'AirPricedItineraries' => $air_priced_itineraries,
                ];
            }


            $air_pricing_groups[] = [
                'AdultTicketAmount' => $adult_ticket_amount,
                'ChildrenTicketAmount' => $children_ticket_amount,
                'InfantTicketAmount' => $infant_ticket_amount,
                'AdultTaxAmount' => $adult_tax_amount,
                'ChildrenTaxAmount' => $children_tax_amount,
                'InfantTaxAmount' => $infant_tax_amount,
                'AgencyFeeAmount' => $agency_fee_amount,
                'AramixFeeAmount' => $aramix_fee_amount,
                'DiscountAmount' => $discount_amount,
                'Total' => $total,
                'AirPricingGroupOptions' => $air_pricing_group_options,
            ];
        }



        // Debug
        // dd([
        // "air_xml" => $air_xml,
        // 'flights_details' => $flights_details['wQe7wMkB0BKA0GeUkLAAAA=='],
        // 'air_segments' => $air_segments['wQe7wMkB0BKAzGeUkLAAAA=='],
        // 'pricing_solutions' => $pricing_solutions['wQe7wMkB0BKAyGeUkLAAAA=='],
        // 'pricing_solutions' => $pricing_solutions,
        // "s_xml" => $s_xml,
        // "air_itineraries" => $air_itineraries,
        // 'air_pricing_groups' => $air_pricing_groups,

        // ]);

        // Build response

        $payload = [
            'count' => count($flights),
            'flights' => $flights,
        ];


        $response = [
            'success' => true,
            'payload' => $payload,
            'raw' => [
                '$flights_details' => $flights_details,
                '$air_segments' => $air_segments,
                '$air_itineraries' => $air_itineraries,
            ],
        ];

        return $response;
    }
}
