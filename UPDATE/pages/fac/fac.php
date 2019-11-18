<div id="fac-page" class="content-page" style="display:none;">
  <div class="container-fluid">
    
    <!-- Content Header (Page header) -->
    <section class="content-header" style="padding:2px;">
      <h1>
        SETUP FACILITIES
      </h1>
      <ol class="breadcrumb" style="padding-top:0px;">
        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Setup Facilities</li>
      </ol>
    </section>

    <!-- Fac Forms -->
    <?php include __DIR__ . '/fac-forms.html'; ?>

    <!-- Fac Table -->
    <?php include __DIR__ . '/fac-table.html'; ?>

    <!-- Fac Modal -->
    <?php include __DIR__ . '/fac-modals.html'; ?>

  </div>
</div>

<script type="text/javascript">

  // A flag to check if it is first time loading, primary use is for click event for fac menu item
  var facFirstLoad = true;

  // FAC menu item click event
  $(".menu-item[page_id='fac-page']").click(function() {
    $("#fac-page select").css('-webkit-appearance', 'menulist');
    $("#fac-page select:disabled").css('-webkit-appearance', 'none');

    clearErrors();
    $("#fac-form-action").val("").change();

    if (facFirstLoad != true) {
      return;
    }
    // loads options for ftyp, ort, spcfnc selection fields in setup facility
    loadFacOptions("queryFtyp", "ftyp", createFacOptions);
    loadFacOptions("queryOrt", "ort", createFacOptions);
    loadFacOptions("querySpcfnc", "spcfnc", createFacOptions);

    // load fac table upon visiting page
    queryFac();

    facFirstLoad = false;
  });
  
  function loadFacOptions(action, type, callback) {
    $.ajax({
      type: 'POST',
      url: "./em/ipcDispatch.php",
      data: {
        api: "ipcOpt",
        act: action,
        user: user.uname,
      },
      dataType: 'json',
    }).done(function(data) {
      callback(data, type);
    });
  }

  function createFacOptions(data, type) {
    if(data.rslt ==="fail") {
      clearErrors();
      inputError($("#fac-form-action"),data.reason);
      return;
    }
    let res = data.rows;
    var a = [];
    a.push('<option value=""></option>');
    
    res.forEach(function(option) {
      let html = `<option value="${option[type]}">${option[type]}</option>`;
      a.push(html);
    });
    
    $('#fac-form-'+type).html(a.join(''));
    $('#fac-modal-'+type).html(a.join(''));
  }
</script>