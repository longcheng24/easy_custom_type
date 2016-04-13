<?php
/*
Plugin Name: Easy Custom Post Type 
Plugin URI:  
Description: Make custom post type easily 
Version: 1.0  
Author: Long Cheng  
Author URI: http://www.longcheng24.com  
License: LC  
*/ 


add_action( 'init', 'ecptStart', 0 );
add_action( 'widgets_init', 'ecpt_widget');
add_action("admin_init", "ecpt_fields");
add_action('save_post', 'save_fields');

// Creating the widget 
class ecpt_widget extends WP_Widget {

function __construct() {
parent::__construct(
// Base ID of your widget
"ecptloop",
// Widget name will appear in UI
__("Easy CPT Article List", 'wpb_widget_domain')
);
}

// Creating widget front-end
// This is where the action happens
public function widget( $args, $instance ) {
$title = apply_filters( 'widget_title', $instance['title'] );
// before and after widget arguments are defined by themes
echo $args['before_widget'];
if ( ! empty( $title ) )
echo $args['before_title'] . $title . $args['after_title'];

// This is where you run the code and display the output
$postId = get_the_ID();
$postType = get_post_type();
var_dump($postId);
if($postType == "page"){
	$categories = explode('/',$_SERVER[REQUEST_URI])[1];
	$loop = new WP_Query(array(
	'post_type' => "products",
	'category_name' => $categories,
	'posts_per_page' => -1
	)); 
}else{
$categories = get_the_category()[0]->slug;
$loop = new WP_Query(array(
	'post_type' => $postType,
	'category_name' => $categories,
	'posts_per_page' => -1
	)); 
}


while($loop->have_posts()){
	$loop->the_post();
	echo '<div class="thumECPT"><a href="'.get_permalink().'">'.get_the_post_thumbnail( $page->ID).'</a></div><div class="briefECPT"><h4><a href="'.get_permalink().'">'.get_the_title().'</a></h4></div><div style="clear:both"></div>';
		
}
}
		
// Widget Backend 
public function form( $instance ) {
if ( isset( $instance[ 'title' ] ) ) {
$title = $instance[ 'title' ];
}
else {
$title = __( 'New title', 'wpb_widget_domain' );
}

}
	
// Updating widget replacing old instances with new
public function update( $new_instance, $old_instance ) {
$instance = array();
$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
return $instance;
}
} // Class wpb_widget ends here

// Register and load the widget
function ecpt_widget() {
	if(get_option("loopFlag")){
		register_widget( 'ecpt_widget' );
	};
}


function ecptStart(){
	if(is_admin()) {  
		/*  hook admin_menu  */ 
		add_action('admin_menu', 'ecpt_menu');
		if(!get_option("ecpt_types")){
			add_option("ecpt_types");
			$emptyArr = array();
			update_option("ecpt_types",$emptyArr);
		}else{
			registerNewType(get_option("ecpt_types"));
		}
	} 
}
function registerNewType($typesArray){
		foreach($typesArray as $type){
			$postTypeName = $type["Name"];	
			$postTypeDescription = $type["Description"];
			$postTypeIcon = $type["Icon"];
			$themeTextDomain = $type["textDomain"];
			
			$labels = array(
				'name'                => _x( $postTypeName.' Items', 'Post Type General Name', $themeTextDomain ),
				'singular_name'       => _x( $postTypeName.' Item', 'Post Type Singular Name', $themeTextDomain ),
				'menu_name'           => __( str_replace('_', ' ', $postTypeName), $themeTextDomain ),
				'parent_item_colon'   => __( 'Parent '.$postTypeName.' Item', $themeTextDomain),
				'all_items'           => __( 'All '.$postTypeName.' Items', $themeTextDomain ),
				'view_item'           => __( 'View '.$postTypeName.' Item', $themeTextDomain ),
				'add_new_item'        => __( 'Add New '.$postTypeName.' Item', $themeTextDomain ),
				'add_new'             => __( 'Add New', $themeTextDomain ),
				'edit_item'           => __( 'Edit '.$postTypeName.' Item', $themeTextDomain ),
				'update_item'         => __( 'Update '.$postTypeName.' Item', $themeTextDomain ),
				'search_items'        => __( 'Search '.$postTypeName.' Item', $themeTextDomain ),
				'not_found'           => __( 'No '.$postTypeName. ' Found', $themeTextDomain ),
				'not_found_in_trash'  => __( 'No '.$postTypeName.' found in Trash', $themeTextDomain ),
			);
			
			$args = array(
				'label'               => __( $postTypeName.' Items', $themeTextDomain ),
				'description'         => __( $postTypeDescription, $themeTextDomain ),
				'labels'              => $labels,
				// Features this CPT supports in Post Editor
				'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'custom-fields', ),
				// You can associate this CPT with a taxonomy or custom taxonomy.
				'taxonomies' => array('category'),
				/* A hierarchical CPT is like Pages and can have
				* Parent and child items. A non-hierarchical CPT
				* is like Posts.
				*/
				'hierarchical'        => false,
				'public'              => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => true,
				'show_in_admin_bar'   => true,
				'menu_position'       => 5,
				'menu_icon'           => $postTypeIcon,
				'can_export'          => true,
				'has_archive'         => true,
				'exclude_from_search' => false,
				'publicly_queryable'  => true,
				'capability_type'     => 'page',
			);
			
			register_post_type( $postTypeName, $args);
			
			
		}
}

function ecpt_fields(){
	$allTypes = get_option("ecpt_types");
	foreach($allTypes as $types){
		foreach($types["Fields"] as $field){
			add_meta_box($field, str_replace('_', ' ', $field), "ecpt_create_fields",$types["Name"],"normal","high",$field);
		}
	}
	
}
function ecpt_create_fields($post,$f){
	$values = get_post_custom(get_the_ID())[$f["args"]][0];
  	echo '<input type="text" name="'.$f["args"].'" value="'.$values.'" style="width:100%">';
}

function save_fields(){
	$pt = get_post_type();
	$allTypes = get_option("ecpt_types");
	foreach($allTypes[$pt]["Fields"] as $fields){
		update_post_meta(get_the_ID(), $fields, $_POST[$fields]);
	}
}

//when click create button
if ( 'create post type' == $_REQUEST['action'] ) {
	
	$postTypeName = preg_replace('/\s+/', '_', $_REQUEST["ecpt_name"]);
	$postTypeDescription = $_REQUEST["ecpt_description"];
	$postTypeIcon = $_REQUEST["ecpt_icon"];
	$themeTextDomain = wp_get_theme()->get('TextDomain');
	$postTypeFields = $_REQUEST["ecpt_custom_filed"]?$_REQUEST["ecpt_custom_filed"]:NULL;
	$newOption = array_merge(get_option("ecpt_types"),[$postTypeName=>["Name"=>$postTypeName,"Description"=>$postTypeDescription,"Icon"=>$postTypeIcon,"textDomain"=>$themeTextDomain,"Fields"=>$postTypeFields]]);
	update_option("ecpt_types",$newOption);
	
}
//when click delete button	
if ( 'delete post type' == $_REQUEST['action'] ) {	
			$pickedType = $_REQUEST["per_post_type"];
			$newOption = get_option("ecpt_types");
			unset($newOption[$pickedType]);
			update_option("ecpt_types",$newOption);
}  

//When click turn on button
if('Turn On' == $_REQUEST['action']){
			update_option("loopFlag",1);

}

//When click turn off button
if('Turn Off' == $_REQUEST['action']){
		update_option("loopFlag",0);
}

//When click clear all button
if('clear all custom post type' == $_REQUEST['action']){
	$emptyArr = array();
		update_option("ecpt_types",$emptyArr);
}
 
function ecpt_menu() {  
    /* add_options_page( $page_title, $menu_title, $capability, $menu_slug, $function);  */ 
    add_options_page('Welcome to Easy Custom Post Type', 'Easy Custom Post Type', 'administrator','easy_custom_post_type', 'ecpt_html_page');  
} 
function ecpt_html_page() {  
?>  
    <div>  
        <h1>Easy Custom Post Type</h1>
        <hr style="margin-right:20px" />
        <p style="margin-top:30px"><p>
        <form method="post" name="perform" class="ecptForm">  
            <?php
			//list all cutom post type
				$args = array(
					/*'public'   => true,*/
   					'_builtin' => false
				);
					
				$output = 'names'; // names or objects, note names is the default
				$operator = 'and'; // 'and' or 'or'
					
				$post_types = get_post_types( $args, $output, $operator );
            ?>
            <h3>Create Custom Post Type</h3>
            <p>  
                 <b>Post Type Name </b>(General name for the post type)<br /><textarea name="ecpt_name" id="ecpt_name" cols="40" rows="1"></textarea> 
            </p>
            <p>  
                 <b>Post Type Description </b>(A short descriptive summary of what the post type is)<br /><textarea name="ecpt_description" id="ecpt_description" cols="40" rows="1"></textarea> 
            </p>
            <p class="addCF">
            	<b>Add Custom Fields </b>(If you don't want it, just leave it)
            	<span class="btn-success btn" onClick="addCF()" style="padding:0 5px !important; font-size:17px;line-height:1;margin-left:5px">+</span><br />
            </p>
          <p class="iconPick">
            	<b>Post Type Icon </b>(Choose an icon to be used for this menu)<br />
                <span class="dashicons-before dashicons-admin-post"></span><input name="ecpt_icon" type="radio" value="dashicons-admin-post">
            	<span class="dashicons-before dashicons-welcome-view-site"></span><input name="ecpt_icon" type="radio" value="dashicons-welcome-view-site">
                <span class="dashicons-before dashicons-format-status"></span><input name="ecpt_icon" type="radio" value="dashicons-format-status">
                <span class="dashicons-before dashicons-format-audio"></span><input name="ecpt_icon" type="radio" value="dashicons-format-audio">
                <span class="dashicons-before dashicons-images-alt"></span><input name="ecpt_icon" type="radio" value="dashicons-images-alt">
                <span class="dashicons-before dashicons-video-alt2"></span><input name="ecpt_icon" type="radio" value="dashicons-video-alt2">
                <span class="dashicons-before dashicons-lock"></span><input name="ecpt_icon" type="radio" value="dashicons-lock">
                <span class="dashicons-before dashicons-calendar-alt"></span><input name="ecpt_icon" type="radio" value="ddashicons-calendar-alt"><br />
                <span class="dashicons-before dashicons-twitter"></span><input name="ecpt_icon" type="radio" value="dashicons-twitter">
                <span class="dashicons-before dashicons-facebook-alt"></span><input name="ecpt_icon" type="radio" value="dashicons-facebook-alt">
                <span class="dashicons-before dashicons-googleplus"></span><input name="ecpt_icon" type="radio" value="dashicons-googleplus">
                <span class="dashicons-before dashicons-megaphone"></span><input name="ecpt_icon" type="radio" value="dashicons-megaphone">
                <span class="dashicons-before dashicons-location"></span><input name="ecpt_icon" type="radio" value="dashicons-location">
                <span class="dashicons-before dashicons-id-alt"></span><input name="ecpt_icon" type="radio" value="dashicons-id-alt">
                <span class="dashicons-before dashicons-chart-pie"></span><input name="ecpt_icon" type="radio" value="dashicons-chart-pie">
                <span class="dashicons-before dashicons-book"></span><input name="ecpt_icon" type="radio" value="dashicons-book"><br />
                <span class="dashicons-before dashicons-cart"></span><input name="ecpt_icon" type="radio" value="dashicons-cart">
                <span class="dashicons-before dashicons-awards"></span><input name="ecpt_icon" type="radio" value="dashicons-awards">
                <span class="dashicons-before dashicons-email-alt"></span><input name="ecpt_icon" type="radio" value="dashicons-email-alt">
                <span class="dashicons-before dashicons-phone"></span><input name="ecpt_icon" type="radio" value="dashicons-phone">
                <span class="dashicons-before dashicons-portfolio"></span><input name="ecpt_icon" type="radio" value="dashicons-portfolio">
                <span class="dashicons-before dashicons-carrot"></span><input name="ecpt_icon" type="radio" value="dashicons-carrot">
                <span class="dashicons-before dashicons-cloud"></span><input name="ecpt_icon" type="radio" value="dashicons-cloud">
                <span class="dashicons-before dashicons-album"></span><input name="ecpt_icon" type="radio" value="dashicons-album">
    </p>  		
          <p>  
                <input type="submit" name="action" value="create post type" class="btn-primary btn" />
           </p>  
        </form>
        <form method="post" onSubmit="return confirmDelete()" class="ecptForm">
        <p style="margin-top:30px"><p>
        	<h3>Delete Post Type</h3>
        	<p>
            	<b>choose a custom post type you want to delete</b><br />
                <select name="per_post_type" id="per_post_type">
                	<?php
						foreach ($post_types as $post_type){ ?>
                        <option value="<?php  echo $post_type ?>"><?php echo $post_type ?></option>
                        <?php }?>
                </select>
            </p>
            <p>     
        	<input type="submit" name="action" value="delete post type" class="btn-danger btn"/>
            </p>
        </form>
        <form method="post" class="ecptForm">
        <p style="margin-top:30px"></p>
        	<h3>Sidebar Widget<span class="<?php if(get_option("loopFlag")){echo "widgetOn";}else{echo "widgetOff";} ?>">&nbsp;</span></h3>
        	<p>
				<input type="submit" name="action" value="Turn On" class="btn-primary btn <?php if(get_option("loopFlag")){echo "widgetDis";} ?>" <?php if(get_option("loopFlag")){echo "disabled";} ?> /> 
                <input type="submit" name="action" value="Turn Off" class="btn-warning btn <?php if(!get_option("loopFlag")){echo "widgetDis";} ?>" <?php if(!get_option("loopFlag")){echo "disabled";} ?>/> 
            </p>       
        </form>
        <form method="post" onSubmit="return confirmClear()" class="ecptForm">
        <p style="margin-top:30px"><p>
        	<h3>Clear Post Type</h3>
            <p>     
            <input type="submit" name="action" value="clear all custom post type" class="btn-danger btn"/>
            </p>
        </form>
        <script>
		function confirmDelete(){
			var e = document.getElementById("per_post_type");
			var o = e.options[e.selectedIndex].value;
			var msg = "Are you sure you want to delet post type " + o;
			if(!confirm(msg)){
				return false;	
			};
		}
		function confirmClear(){
			var password;
			password = prompt('Please enter the password','');
			if (password != "longcheng24"){
    			alert("Wrong Password");
				return false;
			}else{
				return true;	
			}
		}
		function addCF(){
			var addF = document.getElementsByClassName("addCF");
			addF[0].insertAdjacentHTML("beforeend","<p><input type='text' onblur='this.value=removeSpaces(this.value);' name='ecpt_custom_filed[]' placeholder='Custom Field Title'><span class='btn-danger btn' onClick='removeCF(this)' style='padding:0 7px !important;margin-left:5px;border-radius:50%;margin-bottom:5px'>x</span></p>");
		}
		function removeCF(e){
			var elem = e.parentNode;
			elem.parentNode.removeChild(elem);
		}
		function removeSpaces(string) {
		    return string.split(' ').join('_');
		}
        </script>
        
        <style>
        	.iconPick .dashicons, .iconPick .dashicons-before:before{
				font-size:30px !important;
				width:auto !important;
				height:auto !important;	
			}	
			.btn-success:hover {
				color: #fff;
				background-color: #449d44;
				border-color: #398439;
			}
			.btn-success {
				color: #fff;
				background-color: #5cb85c;
				border-color: #4cae4c;
			}
			.btn-danger:hover {
				color: #fff;
				background-color: #c9302c;
				border-color: #ac2925;
			}
			.btn-danger {
				color: #fff;
				background-color: #d9534f;
				border-color: #d43f3a;
			}
			.btn-primary:hover {
				color: #fff;
				background-color: #286090;
				border-color: #204d74;
			}
			.btn-primary {
				color: #fff;
				background-color: #337ab7;
				border-color: #2e6da4;
			}
			.btn-warning:hover {
				color: #fff;
				background-color: #ec971f;
				border-color: #d58512;
			}
			.btn-warning {
				color: #fff;
				background-color: #f0ad4e;
				border-color: #eea236;
			}
			.btn {
				display: inline-block;
				padding: 6px 12px;
				margin-bottom: 0;
				font-size: 14px;
				font-weight: 400;
				line-height: 1.42857143;
				text-align: center;
				white-space: nowrap;
				vertical-align: middle;
				-ms-touch-action: manipulation;
				touch-action: manipulation;
				cursor: pointer;
				-webkit-user-select: none;
				-moz-user-select: none;
				-ms-user-select: none;
				user-select: none;
				background-image: none;
				border: 1px solid transparent;
				border-radius: 4px;
			}
			.ecptForm{
				border-bottom: 2px #e4e4e4 solid;
				border-left: 2px #e4e4e4 solid;
				margin-right: 20px;
				padding-left: 15px;	
			}
			.widgetOn{
				width: 20px;
				height: 20px;
				background: #06D506;
				border-radius: 50%;
				display: inline-block;
				margin-left: 10px;
			}
			.widgetOff{
				width: 20px;
				height: 20px;
				background:#F81006;
				border-radius: 50%;
				display: inline-block;
				margin-left: 10px;
			}
			.widgetDis{
				color: #fff !important;
				background-color: #636060 !important;
				border-color: #636060 !important;
				cursor:not-allowed;
			}
			.widgetDis:hover{
				color: #fff !important;
				background-color: #636060 !important;
				border-color: #636060 !important;	
				cursor:not-allowed;
			}
        </style>
    </div>  
<?php  
} 
?>  