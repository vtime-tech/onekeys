/**

 @Name：layuiAdmin（iframe版） 设置
 @Author：贤心
 @Site：http://www.layui.com/admin/
 @License: LPPL
    
 */
 
layui.define(['form', 'upload'], function(exports){
  var $ = layui.$
  ,layer = layui.layer
  ,laytpl = layui.laytpl
  ,setter = layui.setter
  ,view = layui.view
  ,admin = layui.admin
  ,form = layui.form
  ,upload = layui.upload;

  var $body = $('body');
  
  //自定义验证
  form.verify({
    nickname: function(value, item){ //value：表单的值、item：表单的DOM对象
      if(!new RegExp("^[a-zA-Z0-9_\u4e00-\u9fa5\\s·]+$").test(value)){
        return '用户名不能有特殊字符';
      }
      if(/(^\_)|(\__)|(\_+$)/.test(value)){
        return '用户名首尾不能出现下划线\'_\'';
      }
      if(/^\d+\d+\d$/.test(value)){
        return '用户名不能全为数字';
      }
    }
    
    //我们既支持上述函数式的方式，也支持下述数组的形式
    //数组的两个值分别代表：[正则匹配、匹配不符时的提示文字]
    ,pass: [
      /^[\S]{6,12}$/
      ,'密码必须6到12位，且不能出现空格'
    ]
    
    //确认密码
    ,repass: function(value){
      if(value !== $('#LAY_password').val()){
        return '两次密码输入不一致';
      }
    }
  });
  
  //网站设置
  form.on('submit(set_website)', function(obj){
    // layer.msg(JSON.stringify(obj.field));
    //提交修改
    admin.req({
      url: '/admin/set/systemSet'
      ,method:'POST'
      ,data: obj.field
      ,done: function(res){
          layer.msg(res.msg, {
            icon: 1
          });
      }
    });
    return false;
  });

  //网站设置
  form.on('submit(set_pay)', function(obj){
    //提交修改
    admin.req({
      url: '/admin/set/paySet'
      ,method:'POST'
      ,data: obj.field
      ,done: function(res){
        layer.msg(res.msg, {
          icon: 1
        });
      }
    });
    return false;
  });

  //实名设置
  form.on('submit(set_realname)', function(obj){
    //提交修改
    admin.req({
      url: '/admin/set/realnameSet'
      ,method:'POST'
      ,data: obj.field
      ,done: function(res){
        layer.msg(res.msg, {
          icon: 1
        });
      }
    });
    return false;
  });

  //短信设置
  form.on('submit(set_sms)', function(obj){
    //提交修改
    admin.req({
      url: '/admin/set/smsSet'
      ,method:'POST'
      ,data: obj.field
      ,done: function(res){
        layer.msg(res.msg, {
          icon: 1
        });
      }
    });
    return false;
  });

  //邮件服务
  form.on('submit(set_system_email)', function(obj){
    layer.msg(JSON.stringify(obj.field));
    
    //提交修改
    /*
    admin.req({
      url: ''
      ,data: obj.field
      ,success: function(){
        
      }
    });
    */
    return false;
  });
  
  
  //设置我的资料
  form.on('submit(setmyinfo)', function(obj){
    layer.msg(JSON.stringify(obj.field));
    
    //提交修改
    /*
    admin.req({
      url: ''
      ,data: obj.field
      ,success: function(){
        
      }
    });
    */
    return false;
  });

  //上传头像
  var avatarSrc = $('#LAY_avatarSrc');
  upload.render({
    url: '/api/upload/'
    ,elem: '#LAY_avatarUpload'
    ,done: function(res){
      if(res.status == 0){
        avatarSrc.val(res.url);
      } else {
        layer.msg(res.msg, {icon: 5});
      }
    }
  });
  
  //查看头像
  admin.events.avartatPreview = function(othis){
    var src = avatarSrc.val();
    layer.photos({
      photos: {
        "title": "查看头像" //相册标题
        ,"data": [{
          "src": src //原图地址
        }]
      }
      ,shade: 0.01
      ,closeBtn: 1
      ,anim: 5
    });
  };
  
  
  //修改用户密码
  form.on('submit(setuserpass)', function(obj){
    // layer.msg(JSON.stringify(obj.field));
    //提交修改
    admin.req({
      url: '/user/main/setpass'
      ,method:'POST'
      ,data: obj.field
      ,success: function(res){
        layer.msg(res.msg, {
          icon: 1
          ,time: 1000
        },function (){
          window.parent.location.href='/index/index/login'
        });
      }
    });
    return false;
  });

  //修改管理员密码
  form.on('submit(setadminpass)', function(obj){
    // layer.msg(JSON.stringify(obj.field));
    //提交修改
    admin.req({
      url: '/admin/main/setpass'
      ,method:'POST'
      ,data: obj.field
      ,success: function(res){
        if (res.code == 0)
        {
          layer.msg(res.msg, {
            icon: 1
            ,time: 1000
          },function (){
            window.parent.location.href='/admin/login/login'
          });
        }
      }
    });
    return false;
  });
  
  //对外暴露的接口
  exports('set', {});
});