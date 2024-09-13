<?php
 include_once 'query.php'; include_once 'header.php';
$user_id = $_SESSION['Login_id'];

if (isset($_POST['place_order_new'])) {

    $category = $_POST['category'];
    $sub_category = $_POST['sub_category'];
    $price_data = $_POST['price'];
    $quantity = $_POST['quantity'];
    $category_id = $_POST['category_id'];
    $login_user_name = $_SESSION['Login_user_name'];
    $login_user_id = $_SESSION['Login_id'];

    $name = ucwords($_POST['p_name']);
    $contact_no = $_POST['p_contact_no'];
    $date = $_POST['p_date'];
    $village = $_POST['village'];
    $taluka = $_POST['taluka'];
    $city = $_POST['city'];

    $date_string = explode('-',$date);
    
      $date = $date_string[2].'-'.$date_string[1].'-'.$date_string[0];

      $sql_select_bill_no_query = "SELECT * FROM `order_details` ORDER BY `order_details`.`bill_no` DESC limit 0,1";
      $sql_select_bill = mysqli_query($con,$sql_select_bill_no_query);
      $fatch_bill_no = mysqli_fetch_assoc($sql_select_bill);
      $count_bill = mysqli_num_rows($sql_select_bill);

  if($count_bill>0)
  {
    if (!isset($_SESSION['bill_no'])) {
      $bill_no = $fatch_bill_no['bill_no'];
      $bill_no++;
    }else{
      $bill_no = $_SESSION['bill_no'];
    }
  }
  else
  {
      $bill_no=1;
  }
  

  $total_purchase_product = count($category);

  $order_data_existing = "select * from user where contact_no=$contact_no";
  $user_data_existing = mysqli_query($con,$order_data_existing);
  $count_record = mysqli_num_rows($user_data_existing);
  $address = "";

    for ($i=0; $i<$total_purchase_product; $i++) 
    {

      $sql_update_qty = "select * from stock where cat_id='$category_id[$i]' and sub_cat_name='$sub_category[$i]'";
      $sql_qty_data = mysqli_query($con,$sql_update_qty);
      $sql_qty_data1 = mysqli_query($con,$sql_update_qty);

      if(mysqli_num_rows($sql_qty_data)==0){
        $sql_update_qty = "select * from extra_cat_stock where cat_id='$category_id[$i]' and sub_cat_name='$sub_category[$i]'";
        $sql_qty_data = mysqli_query($con,$sql_update_qty);
        $sql_qty_data2 = mysqli_query($con,$sql_update_qty);

      }

      $qty_data = mysqli_fetch_assoc($sql_qty_data);
      $total_qty = $qty_data['quantity'];
      $pending_data = $total_qty-$quantity[$i];

      if(mysqli_num_rows($sql_qty_data1)!=0){
        $sql_update = "update stock set quantity=$pending_data where cat_id='$category_id[$i]' and sub_cat_name='$sub_cat_name[$i]'";
      }else{
         $sql_update = "update extra_cat_stock set quantity=$pending_data where cat_id='$category_id[$i]' and sub_cat_name='$sub_cat_name[$i]'";
      }

      mysqli_query($con,$sql_update);


        $o_data[] = ["cat_id"=>$category_id[$i],"cat_name"=>$category[$i],"sub_cat_name"=>$sub_category[$i],"price"=>$price_data[$i],"quantity"=>$quantity[$i]];
    } 

    $uder_order_data =  json_encode($o_data,JSON_UNESCAPED_UNICODE);


    if($count_record==0)
    {
       $user_data_record = mysqli_fetch_assoc($user_data_existing);
      $user_id = $user_data_record["u_id"];
      $order_info_query = "insert into user(name,contact_no,address,b_date,village,taluka,city)values('$name','$contact_no','$address','$date','$village','$taluka','$city')";
      $order_info = mysqli_query($con,$order_info_query);
      $user_id= mysqli_insert_id($con);
      $user_id-1;
    }else{
      $order_info_query = "update user set name='$name' , contact_no='$contact_no',address='$address',village='$village',taluka='$taluka',city='$city' where u_id = $user_id";
      mysqli_query($con,$order_info_query);
    }

      for ($i=0; $i<$total_purchase_product; $i++) 
      { 

        $update_quntity_query = "select * from stock where cat_id='$category_id[$i]' and sub_cat_name='$sub_category[$i]'";
        $update_quntity_data = mysqli_query($con,$update_quntity_query);
        $cnt=mysqli_num_rows($update_quntity_data);

        if($cnt==0)
        {
          $update_quntity_query = "select * from extra_cat_stock where cat_id=$category_id[$i] and sub_cat_name='$sub_category[$i]'";
          $update_quntity_data = mysqli_query($con,$update_quntity_query);
        }

        $quntity_update_row = mysqli_fetch_assoc($update_quntity_data);

        $stock_id = $quntity_update_row['s_id'];
        $total_stock = $quntity_update_row['quantity'] - $quantity[$i];

        if($cnt==0)
        {
          $update_stock = "update extra_cat_stock set quantity=$total_stock where s_id=$stock_id";
        }
        else
        {
          $update_stock = "update stock set quantity=$total_stock where s_id=$stock_id";
        }

        mysqli_query($con,$update_stock);

        if($cnt==0)
        {
          $select_sub_cat_id = "select * from extra_sub_category where cat_id=$category_id[$i] and sub_cat_name='$sub_category[$i]'"; 
        }
        else
        {
          $select_sub_cat_id = "select * from sub_category where cat_id=$category_id[$i] and sub_cat_name='$sub_category[$i]'";
        }

          $select_sub_cat_data = mysqli_query($con,$select_sub_cat_id);
          $sub_cat_data_row = mysqli_fetch_assoc($select_sub_cat_data);
      }



     $place_order = "insert into order_details(order_info,user_id,bill_no,order_date,o_place_by)values('$uder_order_data','$user_id','$bill_no','$date','$login_user_name')";
            mysqli_query($con,$place_order);

      $sql_Delete_order_details = "delete from order_data where user_id = $login_user_id";
      mysqli_query($con,$sql_Delete_order_details);

  header('location:view_new.php');
}


$today_date = date("Y-m-d");

$today_order_query = "SELECT product_order.* , user.* FROM `product_order` JOIN user on user.u_id=product_order.user_id GROUP BY product_order.bill_no";
$total_order = mysqli_query($con,$today_order_query);

$today_collection_query = "SELECT * FROM `paid_amount` WHERE date = '$today_date'";
$today_payment_data = mysqli_query($con,$today_collection_query);

$sql_select_order = "select * , sum(quantity) as total_q from order_data where user_id=$user_id group by sub_cat_name , cat_name ORDER BY order_id asc";
$sql_data = mysqli_query($con,$sql_select_order);


$today_payment=0;
$cash=0;
$online=0;

while($payment_row = mysqli_fetch_assoc($today_payment_data))
{
    $today_payment += $payment_row['amount'];

    if($payment_row['payment_mode']==4)
    {
        $cash += $payment_row['amount'];
    }

    if($payment_row['payment_mode']!=4)
    {
        $online += $payment_row['amount'];
    }
}

if (isset($_SESSION['e_cat_id'])) {
  
    $e_cat_id =$_SESSION['e_cat_id'];
    $e_sub_cat_name = $_SESSION['e_sub_cat_name'] ;
    $e_quntity = $_SESSION['e_quntity'];
    $u_id = $_SESSION['u_id'];
    $e_sub_cat_id = $_SESSION['e_sub_cat_id']; 
    $user_data = $_SESSION['user_data']; 
}

$today_date = date("Y-m-d");

$today_order_query = "SELECT product_order.* , user.* FROM `product_order` JOIN user on user.u_id=product_order.user_id GROUP BY product_order.bill_no";
$total_order = mysqli_query($con,$today_order_query);

$today_collection_query = "SELECT * FROM `paid_amount` WHERE date = '$today_date'";
$today_payment_data = mysqli_query($con,$today_collection_query);


$today_payment=0;
$cash=0;
$online=0;

while($payment_row = mysqli_fetch_assoc($today_payment_data))
{
    $today_payment += $payment_row['amount'];

    if($payment_row['payment_mode']==4)
    {
        $cash += $payment_row['amount'];
    }

    if($payment_row['payment_mode']!=4)
    {
        $online += $payment_row['amount'];
    }
}

if (isset($_SESSION['e_cat_id'])) {
  
    $e_cat_id =$_SESSION['e_cat_id'];
    $e_sub_cat_name = $_SESSION['e_sub_cat_name'] ;
    $e_quntity = $_SESSION['e_quntity'];
    $u_id = $_SESSION['u_id'];
    $e_sub_cat_id = $_SESSION['e_sub_cat_id']; 
    $user_data = $_SESSION['user_data']; 
}
?>

  <div class="content-wrapper">
  
    <?php if($_SESSION['role']==1) { ?>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Dashboard</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="dasboard.php">Home</a></li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
  
    <section class="content">
      <div class="container-fluid">
        <!-- Small boxes (Stat box) -->
        <div class="row">

          <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-info">
              <div class="inner">
                <h3><?php echo @$count_today_order_data; ?></h3>

                <p>Today Orders</p>
              </div>
              <div class="icon">
                <i class="ion ion-bag"></i>
              </div>
              <a href="view_order.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          
          <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-success">
              <div class="inner">
                Today Collection:
                <hr>
                <h6> Cash : <?php echo @$cash; ?></h6>
                <hr>
                <h6> Online : <?php echo @$online; ?></h6>
              </div>
              <div class="icon">
                <i class="ion ion-stats-bars"></i>
              </div>
              <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
          <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-warning">
              <div class="inner">
                <h3><?php echo @$total_staff; ?></h3>

                <p>Total Staff</p>
              </div>
              <div class="icon">
                <i class="ion ion-person-add"></i>
              </div>
              <a href="view_staff.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
          <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-danger">
              <div class="inner">
                <h3>65</h3>

                <p>Unique Visitors</p>
              </div>
              <div class="icon">
                <i class="ion ion-pie-graph"></i>
              </div>
              <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <?php } if($_SESSION['role']==1 || $_SESSION['role']!=1) { ?>

   <section class="content">
      <div class="container-fluid">
        
          <div class="row pt-4">
              <div class="col-md-12">
                <!-- general form elements -->
                <div class="card card-primary">
                  <div class="card-header">
                    <h3 class="card-title">Order Details</h3>
                  </div>
                  <!-- /.card-header -->
                  <!-- form start -->
                  <form method="post" id="order_data">
                    <div class="card-body pb-0">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group mb-0">
                                    <label for="exampleInputEmail1">Category:</label>
                                       <select class="form-control form-group" id="c_id_select" name="category">
                                         <option class="form-control">Select category:</option>
                                            <?php while($cat_row = mysqli_fetch_assoc($sel_cat_data1)) { ?>
                                                  <option value="<?php echo $cat_row['cat_id'] ?>"><?php echo $cat_row['cat_name']; ?></option>
                                            <?php } ?>
                                       </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-0">
                                    <label for="exampleInputEmail1">Sub category:</label>
                                        <select class="form-control form-group" id="sub_cat_data" name="sub_category">
                                         <option class="form-control">Select sub category:</option>
                                       </select>
                                </div>
                            </div>
                              <div class="col-md-2">
                                <div class="form-group mb-0">
                                    <label for="exampleInputEmail1">Price:</label>
                                        <input type="number" readonly class="form-control" id="cat_price" name="price">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-0">
                                    <label for="exampleInputEmail1">Quantity:</label>
                                        <input type="number" class="form-control" value="1" name="quantity" min="1" id="quantity_max_value">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-0">
                                    <label for="exampleInputEmail1">&nbsp</label>
                                        <input type="submit" class="btn btn-primary form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                  </form>
                    <div class="card-body pt-0 table-responsive">
                      <table class="table table-bordered text-center cate_table" cellspacing="0" >
                            <thead id="table_header">
                               <tr>
                                  <th>B-1K-1</th>
                                  <th>B-1K-2</th>
                                  <th>B-1K-3</th>
                                  <th>B-1K-4</th>
                                  <th>MR-2</th>
                                  <th>MR-3</th>
                                  <th>MR-4</th>
                                  <th>MR-2 Mini</th>
                                  <th>MR2-B2</th>
                                  <th>MR3-B2</th>
                                  <th>MR4-B2</th>
                                  <th>B-2K-1</th>
                                  <th>B-2K-2</th>
                                  <th>B-2K-3</th>
                                  <th>B-3K-1</th>
                                  <th>B-3K-2</th>
                                  <th>B-4K-1</th>
                                  <th>B-4K-2</th>
                                  <th>B-5K-1</th>
                                  <th>B-5K-2</th>
                               </tr>
                               <tbody id="display_price">
                                 
                               </tbody>
                            </thead>
                        </table>
                    </div>
                </div>
                <!-- /.card -->
              </div>       

          </div>
            <form method="POST">  
                <div class="col-md-12">
                  <!-- general form elements -->
                  <div class="card card-primary">
                    <div class="card-header">
                      <h3 class="card-title">Add Customer info</h3>
                    </div>
                    <!-- /.card-header -->
                    <!-- form start -->
                      <div class="card-body">
                          <div class="row">
                              <div class="col-md-4">
                                  <div class="form-group">
                                      <label for="exampleInputEmail1">Name: <a href=""></a></label>
                                          <input type="text" class="form-control" value="<?php if(isset($_SESSION['e_cat_id'])) { echo $user_data['name']; } ?>" name="p_name" placeholder="Full name" required id="c_name">
                                  </div>
                              </div>
                              <div class="col-md-4">
                                  <div class="form-group">
                                      <label for="exampleInputEmail1">Contact_no:</label>
                                          <input type="text" class="form-control" value="<?php if(isset($_SESSION['e_cat_id'])) { echo $user_data['contact_no']; } ?>" name="p_contact_no" id="Contact_no" maxlength="10" placeholder="Contact No" required>
                                  </div>
                              </div>
                              <div class="col-md-4">
                                  <div class="form-group">
                                      <label for="exampleInputEmail1">Bill Date:</label>
                                          <input type="date" class="form-control" value="<?php if(isset($_SESSION['e_cat_id'])) { echo $user_data['b_date']; } ?>" name="p_date" required id="todayDate">
                                  </div>
                              </div>
                          </div>
                           <div class="row">
                              <div class="col-md-4">
                                  <div class="form-group">
                                      <label for="exampleInputEmail1">Village: <a href=""></a></label>
                                          <input type="text" class="form-control" value="<?php if(isset($_SESSION['e_cat_id'])) { echo $user_data['village']; } ?>" name="village" placeholder="Village" required id="vilage">
                                  </div>
                              </div>
                              <div class="col-md-4">
                                  <div class="form-group">
                                      <label for="exampleInputEmail1">Taluka:</label>
                                          <input type="text" class="form-control" value="<?php if(isset($_SESSION['e_cat_id'])) { echo $user_data['taluka']; } ?>" name="taluka" id="Taluka" maxlength="10" placeholder="Taluka" required>
                                  </div>
                              </div>
                              <div class="col-md-4">
                                  <div class="form-group">
                                      <label for="exampleInputEmail1">City:</label>
                                          <input type="text" class="form-control" value="<?php if(isset($_SESSION['e_cat_id'])) { echo $user_data['city']; } ?>" name="city" placeholder="City" required id="city">
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
                  <!-- /.card -->
                </div>          

                <div class="col-md-12">
                  <!-- general form elements -->
                  <div class="card card-primary">
                    <div class="card-header">
                      <h3 class="card-title">Order</h3>
                    </div>
                      <div class="card-body">

                          <div class="row mb-2" >
                               <div class="col-md-4">
                                  <div class="form-group mb-0">
                                      <label for="exampleInputEmail1">Category:</label>
                                  </div>
                              </div>
                              <div class="col-md-3">
                                 <div class="form-group mb-0">
                                      <label for="exampleInputEmail1">sub Category:</label>
                                  </div>
                              </div>
                                <div class="col-md-2">
                                  <div class="form-group mb-0">
                                      <label for="exampleInputEmail1">Price:</label>
                                  </div>
                              </div>
                              <div class="col-md-2">
                                  <div class="form-group mb-0">
                                      <label for="exampleInputEmail1">Quantity:</label>
                                  </div>
                              </div>
                              <div class="col-md-1 d-flex align-items-end">
                                      <label for="exampleInputEmail1">Delete:</label>
                              </div>
                          </div>

                          <div id="order_display">
                            <?php $total_price = 0; $total_qty=0; while ($row = mysqli_fetch_assoc($sql_data)) { $total_price += $row['price'] * $row['total_q'];  $total_qty += $row['total_q'];  ?>

                                                <input type="hidden" class="form-control" name="category_id[]" value="<?php echo $row['cat_id']; ?>">
                                                <input type="hidden" class="form-control" name="sub_category_id[]" value="<?php echo $row['sub_cat_id']; ?>">
                              <div class="row mb-2" id="order_display">
                                   <div class="col-md-4">
                                      <div class="form-group mb-0">
                                              <input type="text" class="form-control" name="category[]" value="<?php echo $row['cat_name']; ?>" readonly>
                                      </div>
                                  </div>
                                  <div class="col-md-3">
                                     <div class="form-group mb-0">
                                              <input type="text" class="form-control" name="sub_category[]" value="<?php echo $row['sub_cat_name']; ?>" readonly>
                                      </div>
                                  </div>
                                    <div class="col-md-2">
                                      <div class="form-group mb-0">
                                              <input type="number" readonly class="form-control" name="price[]" value="<?php echo $row['price']; ?>">
                                      </div>
                                  </div>
                                  <div class="col-md-2">
                                      <div class="form-group mb-0">
                                              <input type="number" class="form-control" name="quantity[]" value="<?php echo $row['total_q']; ?>">
                                      </div>
                                  </div>
                                  <div class="col-md-1 d-flex align-items-end">
                                      <a href="javascript:void(0)" delete-id="<?php echo $row['order_id']; ?>" class="delete_p_order btn btn-danger"><i class="fa fa-trash"></i></a>
                                  </div>
                              </div>
                            <?php } ?>
                          </div>

                          </div>
                      </div>
                  </div>
                  <!-- /.card -->
                </div>

                <div class="col-md-12">
                  <div class="card card-primary p-3 mb-0"> 
                    <div class="total_amount row py-3" style="display: flex; justify-content: space-between; align-items: center;">

                        <div class="col-md-5">
                          <h4>Total Amount :</h4>
                          <h1 >

                            ₹ <div id="total_price" class="d-inline"><?php echo $total_price; ?></div></h1>
                        </div>
                        <div class="col-md-5">
                           <h4>Total Quantity :</h4>
                          <h1 >
                            Q <div id="total_qty" class="d-inline"> <?php echo $total_qty; ?></div></h1>
                        </div>
                        <div class="col-md-2">
                          <input type="submit" class="btn btn-primary form-control" id="place_order_new" name="place_order_new" value="Place Order" >
                        </div>
                    </div>
                  </div>
                </div>    
            </form>
      </div><!-- /.container-fluid -->
  </section>

    <?php }  ?>
    <!-- /.content -->
  </div>

  <?php include_once 'footer.php'; ?>

   <style>
            .tab_menu .nav-item .nav-link{
              background-color: transparent;
            }
            .tab_menu .nav-item .nav-link.active{
              border: none;
              color: #007bff;
              border-bottom:3px #007bff solid;
            }
            .tab_menu .nav-item:hover .nav-link{
              border-width: 0 0 3px 0;
              border-color: transparent transparent #007bff transparent;
              color: #007bff;
            }
          </style>

<script>

function disabled_button() {

  document.getElementById("place_order_new").disabled=true;

  setTimeout(function(){  
    var element = document.getElementById("place_order_new");
    element.disabled = false;
  }, 10000);
}

$(document).ready(function(){

        var typingTimer;                //timer identifier
        var doneTypingInterval = 1000;

    $('#Contact_no').keyup(function(){
      clearTimeout(typingTimer);

      if ($('#Contact_no').val) 
      {
        var name = $('#c_name').val();
        var text = $("#Contact_no").val();
          if(name.length==0)
          {
          typingTimer = setTimeout(function(){

            $.ajax({
              type:"POST",
              url:"serach_ajax.php",
              data:{"search_txt":text},
              dataType:"json",
              success:function(data){
                $('#c_name').val(data.name);
                $('#c_address').val(data.address);
              }
            })
          },doneTypingInterval);
        }
      }
    })
})

$(document).on('keyup','#srch_cat',function(){
    var srch_txt = $(this).val();

    $.ajax({
      type:"post",
      url:"ajax_cat_srch.php",
      data:{"cat_name_srch":srch_txt},

      success:function(res){
        $('#cat_srch_body').html(res);
      }
    })
})

$(document).on('change','#c_id_select',function(){
  var id = $(this).val();

      $.ajax({
          type:"post",
          url:"select_ajax_category.php",
          data:{"id":id},
          dataType: "json",
          success:function(res){
              $('#sub_cat_data').html(res.category);
              $('#display_price').html(res.price);
          }
      })
})

$(document).on('change','#sub_cat_data',function(){
  var id = $(this).val();

      $.ajax({
          type:"post",
          url:"get_price_ajax.php",
          data:{"id":id},
          dataType:"json",
          success:function(res){
              $('#cat_price').val(res.price);
              $('#quantity_max_value').attr("max",res.quantity);
          }
      })
})

$(document).on('submit','#order_data',function(e){
  e.preventDefault();
  var order_data = $(this).serialize();
  var user_details = $('#user_details').serialize();
    $.ajax({
      type:"get",
      url:"order_data_ajax.php",
      dataType:"json",
      data:order_data,
      success:function(res){
             $('#order_display').html(res.data);
      $('#total_price').text(res.price);
      $('#total_qty').text(res.quantity);
          }

    })
})

$(document).on('click','.delete_p_order',function(){
  var d_id = $(this).attr('delete-id');
  
  $.ajax({
    type:"post",
    url:"delete_order.php",
    dataType:"json",
    data:{"id":d_id},
    success:function(res){
      $('#order_display').html(res.data);
      $('#total_price').text(res.price);
      $('#total_qty').text(res.quantity);
    }
  })
})
</script>

<style>
.table td{
  padding:5px;
}
#table_header tr th{
  width: 120px !important;
}
</style>
