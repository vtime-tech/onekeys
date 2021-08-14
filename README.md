# 玩客建站系统(Onekeys) 
<a title="Releases" target="_blank" href="https://github.com/vtime-tech/onekeys/releases"><img src="https://img.shields.io/github/release/vtime-tech/onekeys.svg?style=flat-square&color=CC6666"></a>
<a title="Code Size" target="_blank" href="https://github.com/vtime-tech/onekeys"><img src="https://img.shields.io/github/languages/code-size/vtime-tech/onekeys.svg?style=flat-square&color=6699FF"></a>
<a title="Downloads" target="_blank" href="https://github.com/vtime-tech/onekeys/releases"><img src="https://img.shields.io/github/downloads/vtime-tech/onekeys/total.svg?style=flat-square&color=99CC99"></a>
<a title="GPL3.0" target="_blank" href="https://www.gnu.org/licenses/gpl-3.0.html"><img src="https://img.shields.io/github/license/vtime-tech/onekeys?style=flat-square"></a>

#### 介绍
玩客建站，第一版开发完成于2018年6月；在鑫迪建站平台上线发布并使用，自平台开放以来受到不少用户青睐，第一版遵循GPL 3.0协议开源。<br>
在广大用户的强烈呼声下，我们将玩客建站系统归纳于188建站系统附属版本，玩客建站系统版本号重置从1.0.0开始迭代更新。
<hr>

#### 环境要求
1. PHP7.4+
2. Apache or Nginx
3. Mysql5.6+
4. Linux
5. Composer1+

#### 安装教程
1. 宝塔PHP设置删除pcntl_wait(),pcntl_signal_dispatch()等函数禁用
2. install.sql导入数据库
3. 修改根目录下的.env文件的数据库信息
4. 设置网站的运行目录为public
5. 对应的php版本安装fileinfo扩展
6. 命令行进入网站根目录运行composer install安装依赖包
7. 命令行进入网站根目录运行php think worker:server -d

#### 使用教程
1. 网站前台直接访问即可
2. 后台地址/admin 账号admin 密码123456

#### 版权声明
1. 遵循GPL 3.0开源协议
2. 欢迎各界大佬的到来，也希望大佬们愿意和我一起修复和维护这一版本

#### 附言
3年前，我们引领了建站系统的风潮。3年后我们重新归来，只为了能完成我们共同的理想与目标。<br>
你们的鼓励与支持是我们前进的动力，让我们继续在这条路上越走越远

#### 交流群
QQ Group: 164483883