<!-- Main Header -->
<header class="main-header">

  <!-- Logo -->
  <a class="logo">
    <!-- mini logo for sidebar mini 50x50 pixels -->
    <span class="logo-mini"><img src="../resources/Telepath_Logo_Part.JPG" height="50" width="50"/></span>
    <!-- logo for regular state and mobile devices -->
    <span class="logo-lg"><b>Telepath</b>Networks&nbsp;Inc.</span>
  </a>
  <!-- Header Navbar -->
  <nav class="navbar navbar-static-top" role="navigation">
    <!-- Sidebar toggle button-->
    <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
      <i class="fas fa-bars"></i>
      <span class="sr-only">Toggle navigation</span>
    </a>
    <!-- Navbar Left Menu -->
    <div class="navbar-custom-menu" style="float:left">
      <ul class="nav navbar-nav">
        <p class="navbar-text" style="margin-top: 10px; margin-bottom: 0;font-size: 20px;"><b>I</b>ntelligent <b>P</b>rovisioning <b>C</b>enter</p>
      </ul>
    </div>
    <!-- Navbar Right Menu -->
    <div class="navbar-custom-menu">
      <ul class="nav navbar-nav">
        <!-- Wire Center Information -->
        <span class="navbar-text" style="margin-top:15px; margin-bottom: 0;">Alarm: </span>
        <span class="navbar-text dropdown" style="margin-top:15px; margin-bottom: 0; margin-left: 0;">
          <button id="alarm-header-icon" type="button" class="btn btn-block btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false"></button>
          <ul id="alarm-header-dropdown" class="dropdown-menu" style="color: #000;">
            <li id="almAck_alarm" class="alarm-header-dropdown-item disabled" psta="NEW"><a>ACK_ALARM</a></li>
            <li id="almUnack_alarm" class="alarm-header-dropdown-item disabled" psta="ACK"><a>UN-ACK_ALARM</a></li>
            <li id="almClr_alarm" class="alarm-header-dropdown-item disabled" psta="SYS-CLR"><a>CLEAR_ALARM</a></li>
          </ul>
        </span>
        <p class="navbar-text" style="margin-top:15px; margin-bottom: 0; margin-right:0">IPC: (<span id="header-ipcstat"></span>) <span id="header-time"></span> <span id="header-timezone"></span>&nbsp;&nbsp;&nbsp;&nbsp;WCC:<span id="header-wc"></span></p>

        <!-- Wire Center Information dropdown -->
        <li class="dropdown notifications-menu">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">
            <i class="fa fa-info-circle"></i>
          </a>
          <ul class="dropdown-menu">
            <li class="header">Wire Center Information</li>
            <div style="padding: 7px 10px;">
              <li>WCN: <span id="header-wcn"></span></li>
              <li>WCC: <span id="header-wcc"></span></li>
              <li>NPANXX: <span id="header-npanxx"></span></li>
              <li>FRMID: <span id="header-frmid"></span></li>
            </div>
          </ul>
        </li>
        
        <!-- Messages: style can be found in dropdown.less-->
        <!-- Notifications Menu -->
        <li id="bulletinBoard-icon">
          <!-- Menu toggle button -->
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">
            <i class="far fa-bell"></i>
            <!-- <span class="label label-warning">10</span> -->
          </a>
        </li>
        
        <!-- Database Dropdown Menu -->
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">
            <i class="fa fa-database"></i>
          </a>
          <ul class="dropdown-menu">
            <li id="backup-database">
              <a>Manual Backup</a>
            </li>
            <li id="download-database">
              <a>Download DB Backup File</a>
            </li>
          </ul>
        </li>
        
        <!-- User Account Menu -->
        <li class="dropdown user user-menu">
          <!-- Menu Toggle Button -->
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">
            <!-- The user image in the navbar-->
            <img id = "dropdown_userPic" src="../PROFILE/defaultUser.jpeg" width=25 height=25 alt="User Image">
            <!-- hidden-xs hides the username on small devices so only the image appears. -->
            <span id="top-nav-user-name" class="hidden-xs">Alexander Pierce</span>
          </a>
          <ul class="dropdown-menu">
            <!-- The user image in the menu -->
            <li class="user-header" style="height: max-content;">
              <img id = "user_header_pic" src="../PROFILE/defaultUser.jpeg" alt="User Image">
              <p>
                <span id="profile-dropdown-user-name">Alexander Pierce</span> - <span id="profile-dropdown-user-group">Web Developer</span>
              </p>
            </li>
            <!-- Menu Body -->
            <!-- <li class="user-body">
            </li> -->
            <!-- Menu Footer-->
            <li class="user-footer"  style="background-color: #3F8CBC;">
              <div class="pull-left">
                <a id="changePw_btn" href="#" class="btn btn-default btn-flat">Change PW</a>
              </div>

              <div class="pull-right">
                <a id="logout-btn" href="#" class="btn btn-default btn-flat">Sign out</a>
              </div>
            </li>
          </ul>
        </li>

        
      </ul>
    </div>
  </nav>
</header>

<?php include __DIR__ . "/modals/header-modal-database.html"; ?>
<?php include __DIR__ . "/modals/header-modal-alarm.html"; ?>
<?php include __DIR__ . "/modals/header-modal-bulletinBoard.html"; ?>

<script type="text/javascript">
  $('#alarm-header-icon').click(function(e) {
    $.ajax({
      type: "POST",
      url: ipcDispatch,
      data: {
        api: "ipcAlm",
        act: "query",
        user: user.uname
      },
      dataType: 'json'
    }).done(function(data) {
      let res = data.rows;
      let modal = {
        title: 'ALARM',
        body: 'THERE IS NO ACTIVE ALARM'
      }

      if (res.length > 0) {
        data.rows.forEach(function(row) {
          if (row.psta === 'NEW') {
            $('#almAck_alarm').removeClass('disabled');
          } else if (row.psta === 'ACK') {
            $('#almUnack_alarm').removeClass('disabled');
          } else if (row.psta === 'SYS-CLR') {
            $('#almClr_alarm').removeClass('disabled');
          }
        });
      } else {
        modalHandler(modal);
      }
    });
  });

  $('.alarm-header-dropdown-item').click(function() {
    if ($(this).hasClass('disabled')) {
      return;
    } else {
      let psta = $(this).attr('psta');

      headerAlmQueryByPsta(psta);
    }
  });

  $('.navbar-text.dropdown').on('hidden.bs.dropdown', function() {
    $('#alarm-header-dropdown').children().addClass('disabled');
  });
  
  function updateUsername() {
    $('#top-nav-user-name, #profile-dropdown-user-name').text(user.fname + ' ' + user.lname);
    $('#profile-dropdown-user-group').text(user.ugrp);
  }

  $("#changePw_btn").click(function(){
    $("#login_username").val(user.uname);
    $("#login_password_input").val("")
    if(!$("#change-password-btn").hasClass("active")) {
      $("#change-password-btn").trigger("click");
    }
    $('#nav-wrapper').hide()
    $('#login-page').show()

  });

  function updateHeaderInfo() {
    let alarmText = '';
    let alarmColor = '';

    if (wcInfo.sev === 'CRI') {
      alarmText = 'CRITICAL';
      alarmColor = 'bg-critical';
    } else if (wcInfo.sev === 'MAJ') {
      alarmText = 'MAJOR';
      alarmColor = 'bg-major';
    } else if (wcInfo.sev === 'MIN') {
      alarmText = 'MINOR';
      alarmColor = 'bg-minor';
    } else if (wcInfo.sev === 'NONE') {
      alarmText = 'NONE';
      alarmColor = 'bg-no-alarm';
    }

    $('#alarm-header-icon').text(alarmText);
    $('#alarm-header-icon').attr('class','btn btn-block btn-xs');
    $('#alarm-header-icon').addClass(alarmColor);


    $('#header-wcn').text(wcInfo.wcname);
    $('#header-wcc').text(wcInfo.wcc);
    $('#header-wc').text(wcInfo.wcc);
    $('#header-npanxx').text(wcInfo.npanxx);
    $('#header-frmid').text(wcInfo.frmid);
    $('#header-ipcstat').text(wcInfo.ipcstat);
    
    let time = moment(wcInfo.time);

    $('#header-time').text(moment().format(wcInfo.date_format + ' HH:mm'));
    $('#header-timezone').text(wcInfo.tzone);
  }


  $(document).ready(function() {

    // Watches logout modal, on close reload page
    $('#logout-modal').on('hidden.bs.modal', function(e) {
      //location.reload();
      let urlParts = window.location.href.split("/");
      urlParts.pop(); 
      urlParts.pop(); 
      urlParts = urlParts.join("/");
      urlParts = urlParts+"?"+Math.floor(Math.random() * 100000);
      window.location = urlParts;
    });

    // Click event for logout button
    $('#logout-btn').click(function() {
      logout('manual logout');
    });

    // Click Event for Database Manual Backup
    $('#backup-database').click(function() {
      clearErrors();
      $('#header-database-backup-modal-action').val('BACKUP MANUALLY');
      $('#header-database-backup-modal').modal('show');
    });

    // Click Event for Database Download Backup File
    $('#download-database').click(function() {
      queryDatabaseDownload();
      $('#header-database-download-modal').modal('show');
    });

    $("#bulletinBoard-icon").click(function() { 
      clearErrors();
      $("#header-bulletinBoard-modal").modal('show');
    });

  })




</script>