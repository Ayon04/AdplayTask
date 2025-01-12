<?php
header('Content-Type: application/json');
function validateRequest($data)
{
    if (!isset($data['id']) || !isset($data['imp']) || !isset($data['device'])) {
        return [false, 'Required parameters are missing from the bid request.'];
    }

    if (!is_array($data['imp']) || count($data['imp']) === 0 ||      !isset($data['imp'][0]['id']) || !isset($data['imp'][0]['bidfloor'])) {
        return [false, 'Invalid impression data in the bid request.'];
    }

    if (!isset($data['device']['os']) || !isset($data['device']['geo']['country'])) {
        return [false, 'Invalid device or geographic data in the bid request.'];
    }

    return [true, ''];
}

function selectBestCampaign($bidRequest, $campaigns)
{
    $selectedCampaign = null;
    $highestPrice = 0;

    foreach ($campaigns as $campaign) {


        if (
                  (strpos($campaign['hs_os'], $bidRequest['device']['os']) !== false || $campaign['hs_os'] === 'No Filter') &&
            (strtolower($campaign['country']) === strtolower($bidRequest['device']['geo']['country']) || !$campaign['country']) &&
            ($campaign['price'] >=  $bidRequest ['imp'][0]['bidfloor'])
        ) {
            if ($campaign['price'] > $highestPrice) {

                $highestPrice = $campaign['price'];

                $selectedCampaign = $campaign;
            }
        }
    }

    return $selectedCampaign;
}

function handleRTBRequest()
{
    $campaigns = [
        [
            "campaignname" => "Test_Banner_13th-31st_march_Developer",
            "advertiser" => "TestGP",
            "code" => "118965F12BE33FB7E",
            "appid" => "20240313103027",
            "tld" => "https://adplaytechnology.com/",
            "creative_type" => "1",
            "creative_id" => 167629,
            "price" => 0.1,
            "hs_os" => "Android,iOS,Desktop",
            "country" => "Bangladesh",
            "image_url" => "https://s3-ap-southeast-1.amazonaws.com/elasticbeanstalk-ap-southeast-1-5410920200615/CampaignFile/20240117030213/D300x250/e63324c6f222208f1dc66d3e2daaaf06.png",
            "url" => "https://adplaytechnology.com/",
            "bidtype" => "CPM"
        ],
        [
            "campaignname" => "Winter_Sale_Campaign_2025",
            "advertiser" => "ShopNow",
            "code" => "A1B2C3D4E5F6G7H8",
            "appid" => "20250113093027",
            "tld" => "https://shopnow.com/",
            "creative_type" => "1",
            "creative_id" => 167630,
            "price" => 0.15,
            "hs_os" => "Android,Desktop",
            "country" => "United States",
            "image_url" => "https://example.com/campaigns/winter_sale.png",
            "url" => "https://shopnow.com/winter-sale",
            "bidtype" => "CPM"
        ],
        [
            "campaignname" => "Spring_Special_Offer",
            "advertiser" => "GreenTech",
            "code" => "Z9Y8X7W6V5U4T3S2",
            "appid" => "20250401123027",
            "tld" => "https://greentech.com/",
            "creative_type" => "2",
            "creative_id" => 167631,
            "price" => 0.2,
            "hs_os" => "iOS,Desktop",
            "country" => "India",
            "image_url" => "https://example.com/campaigns/spring_offer.png",
            "url" => "https://greentech.com/spring-special",
            "bidtype" => "CPM"
        ]
    ];

    $input = file_get_contents('php://input');
    $bidRequest = json_decode($input, true);

    list($isValid, $error) = validateRequest($bidRequest);
    if (!$isValid) {
        http_response_code(400); 
        echo json_encode(["error" => $error]);
        return;
    }

    $selectedCampaign = selectBestCampaign($bidRequest, $campaigns);

    if (!$selectedCampaign) {
        http_response_code(204); // No Content
        return;
    }

    $response = [
        "id" => $bidRequest['id'],
        "seatbid" => [
            [
                "bid" => [
                    [
                        "impid" => $bidRequest['imp'][0]['id'],
                        "price" => $selectedCampaign['price'],
                        "adid" => $selectedCampaign['creative_id'],
                        "adm" => $selectedCampaign['image_url'],
                        "nurl" => $selectedCampaign['url'],
                        "cid" => $selectedCampaign['campaignname'],
                        "crid" => $selectedCampaign['creative_id']
                    ]
                ]
            ]
        ]
    ];

    http_response_code(200);
    echo json_encode($response);
}

handleRTBRequest();
?>
