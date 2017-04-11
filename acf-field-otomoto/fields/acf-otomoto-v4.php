<?php

// exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


// check if class already exists
if( !class_exists('acf_field_otomoto') ) :


class acf_field_otomoto extends acf_field {
	
	// vars
	var $settings, // will hold info such as dir / path
		$defaults; // will hold default field options
		
		
	/*
	*  __construct
	*
	*  Set name / label needed for actions / filters
	*
	*  @since	3.6
	*  @date	23/01/13
	*/
	
	function __construct( $settings )
	{
		// vars
		$this->name = 'otomoto';
		$this->label = __('Otomoto');
		$this->category = __("Choice",'acf'); // Basic, Content, Choice, etc
		$this->defaults = array();


		// do not delete!
    	parent::__construct();
    	
    	
    	// settings
		$this->settings = $settings;

	}




	/*
	*  create_field()
	*
	*  Create the HTML interface for your field
	*
	*  @param	$field - an array holding all the field's data
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*/
	
	function create_field( $field )
	{
		?>
        <div>

            <select name='<?php echo $field['name'] ?>' value="<?php echo $field['value']; ?>">
                <?php
                foreach( $this->getAllOTOMOTO_ID() as $car ) :
                    $selected = ( $field['value'] == $car[1] ? 'selected="selected"' : '' );
                    ?>
                    <option <?php echo $selected; ?> value='<?php echo $car[1]; ?>'><?php echo $car[0]; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
		<?php

    }


    /**
     * @return string
     * Basic path for API
     */
    function APIBase(){
        $options = get_option('otomoto_settings');
        return ( $options['test_mode'] == 'on' ? 'https://otomoto.fixeads.com/api/open' : 'https://ssl.otomoto.pl/api/open');
    }

    /**
     * @return mixed
     *
     * Call adn return token required for authentication
     */

    function returnToken(){
        $options = get_option('otomoto_settings');
        $username = ( $options['test_mode'] == 'on' ? $options['dev_login'] : $options['prod_login']);
        $password = ( $options['test_mode'] == 'on' ? $options['dev_pass']  : $options['prod_pass']);
        $credentials = $options['key_id'].':'.$options['key_secret'];
        $url = $this->APIBase().'/oauth/token';
        $curl_post_data = array(
            "grant_type"=>"password",
            "username"=>$username, // otomoto user email
            "password"=>$password // otomoto user password
        );
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, $credentials); //Your credentials goes here
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //IMP if the url has https and you don't want to verify source certificate

        $curl_response = curl_exec($curl);
        $response = json_decode($curl_response);
        curl_close($curl);


        $token = $response->access_token;
        return $token;
    }


    /**
     * @param $path
     * @return mixed
     *
     * Use this for API call
     */
    function getOTOMOTO($path){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->APIBase().$path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            'Cache-Control: no-cache',
            'Accept: application/json',
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Bearer '.$this->returnToken()
        );

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $server_output = curl_exec ($ch);

        curl_close ($ch);

        $response = json_decode($server_output);

        return $response;
    }


    /**
     * @return array
     * Get all adverts ID to make a list in backend to choose
     */

    function getAllOTOMOTO_ID(){
        $adverts = $this->getOTOMOTO('/account/adverts/');
        $id_array = array();
        foreach ($adverts->results as $advert){
            if( $advert->status != 'removed_by_user') {
                $tmp_array = array(ucfirst($advert->params->make) . ' ' . ucfirst($advert->params->model) . ' ' . ucfirst($advert->title), $advert->id);
                array_push($id_array, $tmp_array);
            }
        }
        return $id_array;
    }

    function printSingleOTOMOTOadvert($data)
    {

        $price = array_values( get_object_vars($data->params->price) );
        $images = get_object_vars( reset($data->photos) );


        $prepared_array = array(
            'url' => $data->url,
            'image' => $images['732x488'],
            'brand' => $data->params->make,
            'model' => $data->params->model,
            'title' => $data->title,
            'price' => $price[1],
            'currency' => $price[2],
            'year' => $data->params->year,
            'mileage' => $data->params->mileage
        );


        $this->templateOTOMOTO($prepared_array);

    }



    function templateOTOMOTO($data){ ?>
        <div class="col-sm-3 OTOMOTO-advert">
            <a href="<?php echo $data["url"]; ?>" target="_blank">
                <div class="embed-responsive embed-responsive-3by2" style="background-image: url(<?php echo $data["image"]; ?>); ">
                    <h3>
                        <?php echo number_format($data["price"], 0, ',', ' '); ?> <span><?php echo $data["currency"]; ?></span>
                    </h3>
                </div>
                <h2>
                    <span><?php echo $data["brand"]; ?></span>
                    <span><?php echo $data["model"]; ?></span>
                    <?php echo $data["title"]; ?>
                </h2>
                <div class="details">
                    <?php echo $data['year']; ?> &#8226; <?php echo number_format($data["mileage"], 0, ',', ' '); ?> km
                </div>

            </a>
        </div>
<?php }


	/*
	*  format_value_for_api()
	*
	*  This filter is applied to the $value after it is loaded from the db and before it is passed back to the API functions such as the_field
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value	- the value which was loaded from the database
	*  @param	$post_id - the $post_id from which the value was loaded
	*  @param	$field	- the field array holding all the field options
	*
	*  @return	$value	- the modified value
	*/
	
	function format_value_for_api( $value, $post_id, $field )
	{
		return $this->printSingleOTOMOTOadvert($this->getOTOMOTO('/account/adverts/' . $value));
	}


}


// initialize
new acf_field_otomoto( $this->settings );


// class_exists check
endif;

?>