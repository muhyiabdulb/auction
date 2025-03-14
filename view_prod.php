<?php include 'admin/db_connect.php' ?>
<?php
	session_start();
	
	if(isset($_GET['id'])){
		$qry = $conn->query("SELECT * FROM products where id= ".$_GET['id']);
		foreach($qry->fetch_array() as $k => $val){
			$$k=$val;
		}
		$cat_qry = $conn->query("SELECT * FROM categories where id = $category_id");
		$category = $cat_qry->num_rows > 0 ? $cat_qry->fetch_array()['name'] : '' ;
	}
?>
<style type="text/css">
	#bid-frm{
		display: none
	}
</style>
<div class="container-fluid">
	<img src="admin/assets/uploads/<?php echo $img_fname ?>" class="d-flex w-100" alt="">
	<p>Name: <large><b><?php echo $name ?></b></large></p>
	<p>Category: <b><?php echo $category ?></b></p>
	<p>Starting Amount: <b id="startBid"><?php echo number_format($start_bid,0) ?></b></p>
	<p>Until: <b><?php echo date("d M Y h:i A",strtotime($bid_end_datetime)) ?></b></p>
	<p>Highest Bid: <b id="hbid"></b></p>
	<p>Description:</p>
	<p class=""><small><i><?php echo $description ?></i></small></p>
	<div class="col-md-12">
		<button class="btn btn-primary btn-block btn-sm" type="button" id="bid">Bid</button>
	</div>
	<div id="bid-frm">
		<div class="col-md-12">
			<form id="manage-bid">
				<input type="hidden" name="product_id" value="<?php echo $id ?>">
				<div class="form-group">
					<label for="" class="control-label">Bid Amount</label>
					<input type="text" class="form-control text-right" id="bid_amount" name="bid_amount" >
				</div>
				<div class="row justify-content-between">
					<button class="btn col-sm-5 btn-primary btn-block btn-sm mr-2">Submit</button>
					<button class="btn col-sm-5 btn-secondary mt-0 btn-block btn-sm" type="button" id="cancel_bid">Cancel</button>
				</div>
			</form>
		</div>
	</div>
</div>
<script>
	var productId = '<?php echo $id ?>';

	$('#imagesCarousel img,#banner img').click(function(){
		viewer_modal($(this).attr('src'))
	})

	$('#participate').click(function(){
        _conf("Are you sure to commit that you will participate to this event?","participate",[productId],'mid-large')
    })
	
    var _updateBid = setInterval(function(){
    	$.ajax({
    		url:'admin/ajax.php?action=get_latest_bid',
    		method:'POST',
    		data:{product_id:productId},
    		success:function(resp){
    			if(resp && resp > 0){
    				$('#hbid').text(parseFloat(resp ?? 0).toLocaleString('en-US',{style:'decimal',maximumFractionDigits:0,minimumFractionDigits:0}))
    			} else {
					$('#hbid').text(parseFloat(0).toLocaleString('en-US',{style:'decimal',maximumFractionDigits:0,minimumFractionDigits:0}))
				}
    		}
    	})
    },1000)

	$(document).ready(function() {
		$('#bid_amount').on('input', function() {
			let value = $(this).val().replace(/[^0-9]/g, ''); // Hapus semua karakter kecuali angka
			if (value === '') {
				$(this).val('');
				return;
			}

			let formatted = new Intl.NumberFormat('id-ID', {
				style: 'currency',
				currency: 'IDR',
				minimumFractionDigits: 0
			}).format(value);

			$(this).val(formatted.replace(/\s/g, '').replace('Rp', 'Rp')); // Hilangkan spasi
		});
	});

	$('#manage-bid').submit(function(e) {
		e.preventDefault();
		start_load();	

		var startBid = $('#startBid').text().replace(/[^0-9]/g, '');  

		// Ambil nilai bid tertinggi
		var latest = $('#hbid').text().replace(/[^0-9]/g, '');  
		var latestBid = parseFloat(latest) || 0;

		// Ambil nilai bid user
		var userBid = $('[name="bid_amount"]').val().replace(/[^0-9]/g, '');
		var bidAmount = parseFloat(userBid) || 0;

		// console.log("bidAmount ", bidAmount)
		// console.log("latestBid ", latestBid)

		if (bidAmount === 0 || bidAmount === null || bidAmount === '' || bidAmount === '0') {
			alert_toast("Bid amount must be filled.", 'danger');
			end_load();
			return false;
		}

		if(latestBid === 0 || bidAmount === '0') {
			if (bidAmount < startBid) {
				alert_toast("Bid amount must be greater than or equal to the current starting bid.", 'danger');
				end_load();
				return false;
			}
		} else {
			// Cek apakah bid lebih besar dari highest bid
			if (bidAmount <= latestBid) {
				alert_toast("Bid amount must be greater than the current Highest Bid.", 'danger');
				end_load();
				return false;
			}

			// Validasi: hanya boleh kelipatan 500.000 dari highest bid
			if ((bidAmount - latestBid) % 500000 !== 0) {
				alert_toast("Bid amount must be a multiple of Rp500,000 from the highest bid.", 'danger');
				end_load();
				return false;
			}
		}

		 // Ubah value di input agar dikirim dalam angka murni
		 $('[name="bid_amount"]').val(parseInt(bidAmount));

		// Jika valid, lanjutkan submit
		$.ajax({
			url: 'admin/ajax.php?action=save_bid',
			method: 'POST',
			data: $(this).serialize(),
			success: function(resp) {
				if (resp == 1) {
					$('[name="bid_amount"]').val('');
					alert_toast("Bid successfully submitted", 'success');
				} else if (resp == 2) {
					alert_toast("The current highest bid is yours.", 'danger');
				}
				end_load();
			},
			error: function(xhr, status, error) {
				console.error("AJAX Error:", status, error);
				console.error("Response Text:", xhr.responseText);
				alert_toast("An error occurred while submitting your bid.", 'danger');
				end_load();
			}
		});
	});

    $('#bid').click(function(){
    	if('<?php echo isset($_SESSION['login_id']) ? 1 : '' ?>' != 1){
    		$('.modal').modal('hide')
    		 uni_modal("LOGIN",'login.php')
    		 return false;
    	}
    	$(this).hide()
    	$('#bid-frm').show()
    });

    $('#cancel_bid').click(function(){
    	$('#bid').show()
    	$('#bid-frm').hide()
    })
</script>
