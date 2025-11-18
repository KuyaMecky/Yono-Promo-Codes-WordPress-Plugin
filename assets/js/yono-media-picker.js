(function($){
  $(document).on('click', '.yg-media-select', function(e){
    e.preventDefault();
    var $btn = $(this);
    var target = $btn.data('target');
    var preview = $btn.data('preview');

    var frame = wp.media({
      title: 'Select / Upload Logo',
      button: { text: 'Use this image' },
      multiple: false
    });

    frame.on('select', function(){
      var att = frame.state().get('selection').first().toJSON();
      $(target).val(att.url).trigger('change');
      $(preview).attr('src', att.url).show();
      $btn.siblings('.yg-media-remove').show();
    });

    frame.open();
  });

  $(document).on('click', '.yg-media-remove', function(e){
    e.preventDefault();
    var $btn = $(this);
    var target = $btn.data('target');
    var preview = $btn.data('preview');
    $(target).val('');
    $(preview).hide().attr('src','');
    $btn.hide();
  });
})(jQuery);
