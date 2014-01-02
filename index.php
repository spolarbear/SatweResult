<?php 

$file_names = array(
	"CPJ"=>"WGCPJ.OUT",
	"ZX"=>"WZQ.OUT",
	"ZXX"=>"WMASS.OUT",
	"WY"=>"WDISP.OUT"
);
$fold = "work";

function echoClass($t,$c=""){
	//return $t;
	if($c)return "<span class='".$c."'>".$t."</span>";
	else return $t;
}

/*
超配筋CPJ文件
	
	超配筋情况
*/
$file_name=$fold."/".$file_names["CPJ"];
$fp=fopen($file_name,'r');
$floorNum=0;//count start at the top of the building
while(!feof($fp)){
	$buffer=fgets($fp,4096);
	$buffer = iconv("gbk", "UTF-8//IGNORE", $buffer); // 编码转换
	$split="----------------------------------------------------------";
	if(trim($buffer)==$split && $floorLine!=3){
			$floorLine=1;
			$floorNum++;
	}
	if($floorLine>3){
		//$floor[$floorNum].=($buffer);
		$floor[$floorNum].=(trim($buffer)?$buffer."<br>":"");
	}
	$floorLine++;
}
fclose($fp);

/*
振型文件
	
	有效质量
	最大地震方位角
	振型剪力排序
	剪重比
*/
$file_name=$fold."/".$file_names["ZX"];
$fp=fopen($file_name,'r');
$zxType=0;//1 周期  2 X向剪力   3 Y向剪力
$line=1;
while(!feof($fp)){
	$buffer=iconv("gbk", "UTF-8//IGNORE", fgets($fp,4096));
	$buffer=trim(ereg_replace("[ ]{1,}"," ",$buffer)); 
	$checkText="方向的有效质量系数";
	if(stripos(trim($buffer),$checkText)>-1){
		$temp = split(":",$buffer);
		$temp = str_replace("%","",$temp[1]);
		$invodePrecent[]=$temp;
	}
	$checkText="地震作用最大的方向";
	if(stripos(trim($buffer),$checkText)>-1){
		$temp = split("=",$buffer);
		$temp = str_replace("(度)","",$temp[1]);
		$earthquakeDegree=$temp;
	}
	if($zxLine>0){
		if($buffer!="")$zx[$zxType][$zxLine]=($buffer);
		$zxLine++;
	}
	if(strpos($buffer,"振型号")>-1){$zxLine=1;$zxType++;}
	elseif($buffer=="" && $zxLine>3)$zxLine=0;
	//剪重比start
	$checkTextJzb="向楼层最小剪重比";
	if(stripos(trim($buffer),$checkTextJzb)>-1){
		$minJzbGf[]=$buffer;
		$minJzb[]=$wyLine[$line-3];
	}
	$wyLine[$line]=$buffer;
	$line++;
	//剪重比end
}
fclose($fp);
//振型处理
foreach ($zx[1] as $key => $value)$zxArr[1][$key] = split(" ",$value);
foreach ($zx[2] as $key => $value)$zxArr[2][$key] = split(" ",$value);
foreach ($zx[3] as $key => $value)$zxArr[3][$key] = split(" ",$value);
for($i=0;$i<count($zxArr[2]);$i++)$zxArr[2][$i]=$zxArr[2][$i][1];
for($i=0;$i<count($zxArr[3]);$i++)$zxArr[3][$i]=$zxArr[3][$i][1];
//1、2振型高亮扭转
if($zxArr[1][1][7]>0.5)$zxArr[1][1][7]=echoClass($zxArr[1][1][7],"b");
if($zxArr[1][2][7]>0.5)$zxArr[1][2][7]=echoClass($zxArr[1][2][7],"b");
if($zxArr[1][3][7]>0.5)$zxArr[1][3][7]=echoClass($zxArr[1][3][7],"udl");
//高亮控制剪力
foreach ($zxArr[1] as $key => $value) {
	if($zxArr[2][$key]>max($zxArr[2])*0.7)$zxArr[4][$key]=echoClass($zxArr[2][$key],"g");
		else $zxArr[4][$key]=$zxArr[2][$key];
	if($zxArr[3][$key]>max($zxArr[3])*0.7)$zxArr[5][$key]=echoClass($zxArr[3][$key],"g");
		else $zxArr[5][$key]=$zxArr[3][$key];
}
//剪重比后处理
$temp = split("=",$minJzbGf[0]);$minJzbGf[0]=$temp[1];
$temp = split("=",$minJzbGf[1]);$minJzbGf[1]=$temp[1];
$checkText=str_replace(" ","",$minJzb[0]);
preg_match("/\((.*?)%\)/",$checkText,$res);
$minJzb[0] = $res[1];
$checkText=str_replace(" ","",$minJzb[1]);
preg_match("/\((.*?)%\)/",$checkText,$res);
$minJzb[1] = $res[1];
$minJzbGf[0]=str_replace("%","",$minJzbGf[0]);
$minJzbGf[1]=str_replace("%","",$minJzbGf[1]);
//地震作用角后处理
$temp = abs($earthquakeDegree);
if(
	($temp>"15" && $temp<"75")
	||
	($temp>"105" && $temp<"165")
)$earthquakeDegree = echoClass($earthquakeDegree,"b");
else $earthquakeDegree = echoClass($earthquakeDegree,"g");



function splitNum($text,$char=":"){
	$temp = split($char,$text);
	$temp = split("\(",trim($temp[1]));
	return $temp[0];	
}
/*
位移文件
*/
$file_name=$fold."/".$file_names["WY"];
$fp=fopen($file_name,'r');
$line=0;
$gongKuang=0;
while(!feof($fp)){
	$buffer=iconv("gbk", "UTF-8//IGNORE", fgets($fp,4096)); // 编码转换
	$buffer=trim(ereg_replace("[ ]{1,}"," ",$buffer)); 

	if(stripos(trim($buffer),"工况")>-1){
		$gongKuang++;
		$gongKuangText[]=ereg_replace("===|工况","",ereg_replace("下的楼层最大位移","",$buffer));

	}
	$checkText="方向最大层间位移角";
	if(stripos(trim($buffer),$checkText)>-1){
		$temp = ereg_replace("1/","",splitNum($buffer));
		$temp = ereg_replace("\.","",$temp);
		$maxWy[$gongKuang][0]=$temp;
	}elseif(stripos(trim($buffer),"方向最大位移与层平均位移的比值")>-1){
		$maxWy[$gongKuang][1]=splitNum($buffer);
	}elseif(stripos(trim($buffer),"方向最大层间位移与平均层间位移的比值")>-1){
		$maxWy[$gongKuang][2]=splitNum($buffer);
	}
}
fclose($fp);

/*
总信息文件
*/
$file_name=$fold."/".$file_names["ZXX"];
$fp=fopen($file_name,'r');
while(!feof($fp)){
	$buffer=iconv("gbk", "UTF-8//IGNORE", fgets($fp,4096)); // 编码转换
	$buffer=trim(ereg_replace("[ ]{1,}"," ",$buffer));
	//地震方向角
	$checkText="斜交抗侧力构件方向的附加地震方向角";
	if(stripos(trim($buffer),$checkText)>-1){
		$temp = split("=",$buffer);
		$earthquakeDegreeSet=$temp[1];
	}
	//刚重比
	$checkText="向刚重比";
	if(stripos(trim($buffer),$checkText)>-1){
		$temp = split("=",$buffer);
		$gzb[]=$temp[1];
	}
	$checkText="该结构刚重比";
	if(stripos(trim($buffer),$checkText)>-1){
		$gzbText[]=$buffer;
	}
	//薄弱层
	$checkText="薄弱层地震剪力放大系数";
	if(stripos(trim($buffer),$checkText)>-1){
		$brc=splitNum($buffer,"=");
		$brcArr[]=$brc;
	}
}
fclose($fp);

//刚重比后处理
if(!stripos($gzbText[0],",能够通过高规")>-1)
	 $gzbText[0]=echoClass($gzbText[0],"b");
else $gzbText[0]=echoClass($gzbText[0],"g");
if(!stripos($gzbText[1],"可以不考虑重力二阶效应")>-1)
	$gzbText[1]=echoClass($gzbText[1],"b");
else $gzbText[1]=echoClass($gzbText[1],"g");

?> 
<html>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script src="jquery-1.7.js" type="text/javascript" ></script>
<script src="cookies.js" type="text/javascript" ></script>
<link href="css/bootstrap.min.css" rel="stylesheet" >
<style>
.b{
	color:red;
	font-weight: bold;
}
.g{
	color:green;
	font-weight: bold;
}
.o{
	color: orange;
	font-weight: bold;
}
.udl{
	font-style: underline;
}
.zq , .cpjfloor{cursor: pointer;}
table{font-size: 12px;}
#divide{color:blue;}
.formhide{display: none}
</style>
<script type="text/javascript">
var x=0;y=0;
var cur="x";
var result="???";
function highLigthBigger(text,num){
	if(text>num)return "<span class='b'>"+text+"</span>";
	else return "<span class='g'>"+text+"</span>";;
}
function cpjShow(num){
	$("div[id^=cpj]").css("display","none");
	$("#cpj"+num).css("display","");
}

function add(key,obj){
	if(cur=="x"){
		x=key;
		y=0;
		result="???";
		cur="y";
		$(".zq").css({"color":"","font-weight":""});
		$(obj).css({"color":"red","font-weight":"bold"});

	}else{
		y=key;
		cur="x";
		$(obj).css({"color":"orange","font-weight":"bold"});
	}
	if(x>0 && y>0 )result=(x/y).toFixed(2);

	$("#divide").html("周期比："+(x?x:"请点击")+" / "+(y?y:"请点击")+" = "+ highLigthBigger(result,0.9) );
}
</script>
<body>
<div class="container">
	<div class="navbar navbar-static-top">
		<ul class="breadcrumb">
			<li>Satwe文本结果</li><!--<span class="divider">/</span> -->
			<li class="active" style="float: right;">@Yangxin</li>
		</ul>
	</div>
</div>
<div class="container" style="font-size: 12px;">
	<div class="row">
		<div class="span6">
			<h3>振型</h3>
			（并列剪力；高亮扭转大于0.5的1、2振型；高亮最大剪力；计算周期比高亮大于0.9）
			<div class="well">
				有效质量系数（高亮小于90）：X方向<span class='<?=($invodePrecent[0]>90?"g":"b")?>'><?=$invodePrecent[0]?>%</span>；Y方向<span class='<?=($invodePrecent[1]>90?"g":"b")?>'><?=$invodePrecent[1]?>%</span>。
				<br/>
				地震作用方向：<?=$earthquakeDegree?>(设定:<?=($earthquakeDegreeSet?$earthquakeDegreeSet:"无")?>)
				<br/>
				<span id='divide'>周期比：请点击 / ??? = ???</span>
			</div>
			<table class="table table-striped table-bordered table-condensed table-hover">
				<thead>
				<tr><th>振型号</th><th>周 期</th><th>转 角</th><th>平动系数 (X+Y)</th><th>扭转系数</th><th>X向剪力</th><th>Y向剪力</th></tr>
				</thead>
			<?php
			foreach ($zxArr[1] as $key => $value) {
				echo "<tr ".($key>6?"class='formhide'":"")."><td>".$value[0]."</td><td class='zq' onclick=\"add('".$value[1]."',this)\">".$value[1]."</td><td>".$value[2]."</td><td>".$value[3].$value[4].$value[5].$value[6]."</td><td>".$value[7]."</td><td>".$zxArr[4][$key]."</td><td>".$zxArr[5][$key]."</td></tr>";
			}
			?>
				<tr>
                  <td colspan="7" onclick="$('.formhide').show();$(this).hide()"><center style="cursor:pointer">
                      <i class=" icon-chevron-down"></i>
                    </center></td>
                </tr>
		    </table>
			<h3>超配筋情况</h3>
			<?php
			$floor = array_reverse($floor);//翻转后从0开始
			foreach ($floor as $key => $value) {
				if(trim($value)!="")$cpjEcho.="<span class='cpjfloor' onclick='cpjShow(".($key+1).")'>".($key+1)."F</span> ";
			}
			if($cpjEcho){
				echo "超配筋层：".echoClass($cpjEcho,"b");
				echo "<div style='border:1px solid gray;padding:10px;height:120px;overflow:auto'>";
				foreach ($floor as $key => $value) {
					echo "<div id='cpj".($key+1)."' style='display:none'>".trim($value)."</div>";
				}
				echo "</div>";
			}else echo echoClass("无","g");
			?>
		</div>
		<div class="span6">

			<h3>整体控制指标</h3>
			<div class="well">
				<h4>剪重比：</h4>
					注：单塔，取最底层剪重比。
					<br>
					<?php
						echo "X向：".$minJzb[0].($minJzb[0]>$minJzbGf[0]?echoClass("大于","g"):echoClass("小于","b"))."(".trim($minJzbGf[0]).")";
						echo "<br>Y向：";
						echo $minJzb[1].($minJzb[1]>$minJzbGf[1]?echoClass("大于","g"):echoClass("小于","b"))."(".trim($minJzbGf[1]).")";
					?>
				<h4>刚重比：</h4>
					X向<?=$gzb[0]?>、Y向<?=$gzb[1]?>
					<br/>
					<?=$gzbText[0]?>
					<br/>
					<?=$gzbText[1]?>
				<h4>位移比：</h4>
					注：高亮小于550、1000的。
					<br>
					<div class="alert alert-info">
					<h5>设定：</h5>
					<style>label{font-size: 12px}</style>
					<label class="checkbox-inline" id="hideOuranLabel">
						<input name="hideOuran" id="hideOuran" type="checkbox" value="hideOuran" onclick="c('hideOuran');toggleClass('ouran');" checked>
						隐藏偶然偏心
					</label>
					高亮临界值（红-绿-橙）：<span class="msg"></span> 
					<span class="daisy-range-picker"></span>
					</div>
					<table class="table table-striped table-bordered table-condensed table-hover">
						<thead>
						<tr><th>工况</th><th title="最大层间位移角">层间位移角</th><th title="最大位移与层平均位移的比值">位移比</th><th title="最大层间位移与平均层间位移的比值(1.2~1.4/1.5)">层间位移比</th></tr>
						</thead>
					<?php
					$i=0;
					foreach ($maxWy as $key => $value) {
						if(stripos($gongKuangText[$i],"偶然偏心")>-1)$ifouran="class='ouran'";else $ifouran="";
						echo "<tr ".$ifouran."><td>".$gongKuangText[$i]."</td><td class='wy' fz='".$value[0]."'>".($value[0]?("1/".trim($value[0])):"-")."</td><td class='".($value[1]<1.2?"g":($value[1]<1.4?"o":"b"))."'>".$value[1]."</td><td class='".($value[2]<1.2?"g":($value[2]<1.4?"o":"b"))."'>".$value[2]."</td></tr>";
						$i++;
					}
					?>
				    </table>
					<h4>薄弱层、刚度比：</h4>
					<table class="table table-striped table-bordered table-condensed">
						<tr><th>层</th><th>薄弱层放大系数</th></tr>
						<?php
						foreach ($brcArr as $key => $value) {
							if($value>1.00)
								echo "<tr><td>".($key+1)."</td><td class='".($value<=1.00?"g":"b")."'>".$value."</td></tr>";
						}
						?>
					</table>
			</div>
		</div>
	</div>
</div>
		<script type="text/javascript" src="js/jquery.event.drag-2.2.js"></script>
		<script type="text/javascript" src="js/jquery.event.drag.live-2.2.js"></script>
		<script type="text/javascript" src="js/jquery.range-pikcer.js"></script>
		<style type="text/css">
			.range-picker-axis{padding:0;position:relative;width:198px;height:2px;border:1px solid #CCC;display:inline-block;border-radius:2px;z-index:1;}
			.range-picker{position:absolute;width:10px;height:10px;border:1px solid #BBB;z-index:3;top:-5px;border-radius:2px;cursor:pointer;background-color:#FFF;}
			.range-picker.left{left:-4px;}
			.range-picker.right{right:-4px;}
			.range-picker.active{background-color:#CCC;}
			.range-selected{background-color:green;/*#CCC*/z-index: 2;position: absolute;width:100%;}
		</style>	
<script type="text/javascript">
function c(text){
	if(readCookie(text) == 1)eraseCookie(text);
	else setCookie(text,1);
}
function toggleClass(classname){
	$("."+classname).toggle();
}
if(readCookie("hideOuran") == 1){
	$("#hideOuran").prop("checked",true);
	//$("#hideOuranLabel").css("color","red");
	toggleClass("ouran");
}
else $("#hideOuran").prop("checked",false);

$(function(){
	var wymin = readCookie("wymin");
	var wymax = readCookie("wymax");
	picker = $(".daisy-range-picker").range_picker({
		show_seperator:true,
		can_switch:true,
	 	animate:false,
		from:(wymin?wymin:800),
		to:(wymax?wymax:1000),
		axis_width:350,
		picker_width:14,
		ranges:[500,550,600,650,700,750,800,850,900,950,1000,1050,1100,1150,1200],
		onChange:function(from_to){
			$(".msg").html(from_to[0]+'~'+from_to[1]);
			setCookie("wymin",from_to[0]);//设定cookie
			setCookie("wymax",from_to[1]);//设定cookie
			$(".wy").each(function(){
				//alert($(this).text());
    			if($(this).attr("fz")>(from_to[1]))$(this).css("color","orange");
    			else if($(this).attr("fz")<(from_to[0]))$(this).css("color","red");
    			else $(this).css("color","green");
  			});
		},
		onSelect:function(index,from_to){
			$(".msg").html(from_to[0]+'~'+from_to[1]);
		},
		afterInit:function(){
			var picker = this;
			var ranges = picker.options.ranges;
			$(".msg").html(picker.options.from+'~'+picker.options.to);
			//初始化
			$(".wy").each(function(){
				//alert($(this).attr("fz")+picker.options.from+'~'+picker.options.to);
    			if($(this).attr("fz")>parseInt(picker.options.to))$(this).css("color","orange");
    			else if($(this).attr("fz")<parseInt(picker.options.from))$(this).css("color","red");
    			else $(this).css("color","green");
  			});

		}
	});
});



</script>
</body>
</html>
