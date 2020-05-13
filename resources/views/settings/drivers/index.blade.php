@extends('layouts.settings.default')
@section('settings_title',trans('lang.drivers_table'))
@section('settings_content')
  @include('flash::message')
  <div class="card">
    <div class="card-header">
      <ul class="nav nav-tabs align-items-end card-header-tabs w-100">
        <li class="nav-item">
          <a class="nav-link active" href="{!! url()->current() !!}"><i class="fa fa-list mr-2"></i>{{trans('lang.drivers_table')}}</a>
        </li>
        <li class="nav-item">
        <!--   <a class="nav-link " href="{!! route('users.create') !!}"><i class="fa fa-plus mr-2"></i>{{trans('lang.user_create')}}</a> -->
        </li>
        @include('layouts.right_toolbar', compact('dataTable'))
      </ul>
    </div>
    <div class="card-body">
      @include('settings.drivers.table')
      <div class="clearfix"></div>
    </div>
  </div>
</div>


@endsection

@prepend('scripts')
<script type="text/javascript">

$(document).ready(function(){

    $(".is_active").val("0");
    
    // $('input[type=checkbox]').on("change",function () {
      $(document).on('change','input[type=checkbox]',function(){

        
        var user_id = $(this).attr('data-id');
        
       if($(this).prop('checked') == true)
        {
          $(".is_active").val("1");
        }
        else{
          $(".is_active").val("0");
        }  
        var active = $(".is_active").val();
          
        $.ajax({
          type: "GET",
          url: "{{ url('settings/drivers_available/checkActiveOrNot') }}/"+user_id,
          data:{'active':active,'user_id':user_id},
          success:function(res)
          {
            console.log(res);
          }
        });
        
    }); 

});
</script>
@endprepend