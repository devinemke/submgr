// 2018-05-13
// DE: change fixedtooltip() to tooltip_show()
// DE: change delayhidetip() to tooltip_hide()
// DE: change #fixedtipdiv to #tooltip_div in CSS
// DE: add "visibility: hidden;" to CSS

/***********************************************
* Fixed ToolTip script- (c) Dynamic Drive (www.dynamicdrive.com)
* Please keep this notice intact
* Visit http://www.dynamicdrive.com/ for full source code
***********************************************/

// DE: set defaults
var tipwidth = "200px"; // default tooltip width
var tipbgcolor = "white"; // tooltip bgcolor
var disappeardelay = 0; // tooltip disappear speed onMouseout (in miliseconds)
var vertical_offset = "0px"; // horizontal offset of tooltip from anchor link
var horizontal_offset = "0px"; // horizontal offset of tooltip from anchor link

// No further editting needed

var ie4=document.all
var ns6=document.getElementById&&!document.all

if (ie4||ns6)
document.write('<div id="fixedtipdiv" style="visibility:hidden;width:'+tipwidth+';background-color:'+tipbgcolor+'" ></div>')

function getposOffset(what, offsettype){
var totaloffset=(offsettype=="left")? what.offsetLeft : what.offsetTop;
var parentEl=what.offsetParent;
while (parentEl!=null){
totaloffset=(offsettype=="left")? totaloffset+parentEl.offsetLeft : totaloffset+parentEl.offsetTop;
parentEl=parentEl.offsetParent;
}
return totaloffset;
}

function showhide(obj, e, visible, hidden, tipwidth){
if (ie4||ns6)
dropmenuobj.style.left=dropmenuobj.style.top=-500
// DE: so width will reset
if (tipwidth != "")
{
	dropmenuobj.widthobj = dropmenuobj.style;
	// DE: use maxWidth rather than width
	// dropmenuobj.widthobj.width = tipwidth + "px";
	dropmenuobj.widthobj.maxWidth = tipwidth + "px";
}
else
{
	dropmenuobj.widthobj = dropmenuobj.style;
	dropmenuobj.widthobj.width = "";
}
if (e.type=="click" && obj.visibility==hidden || e.type=="mouseover")
obj.visibility=visible
else if (e.type=="click")
obj.visibility=hidden
}

function iecompattest(){
return (document.compatMode && document.compatMode!="BackCompat")? document.documentElement : document.body
}

function clearbrowseredge(obj, whichedge){
var edgeoffset=(whichedge=="rightedge")? parseInt(horizontal_offset)*-1 : parseInt(vertical_offset)*-1
if (whichedge=="rightedge"){
var windowedge=ie4 && !window.opera? iecompattest().scrollLeft+iecompattest().clientWidth-15 : window.pageXOffset+window.innerWidth-15
dropmenuobj.contentmeasure=dropmenuobj.offsetWidth
if (windowedge-dropmenuobj.x < dropmenuobj.contentmeasure)
edgeoffset=dropmenuobj.contentmeasure-obj.offsetWidth
}
else{
var windowedge=ie4 && !window.opera? iecompattest().scrollTop+iecompattest().clientHeight-15 : window.pageYOffset+window.innerHeight-18
dropmenuobj.contentmeasure=dropmenuobj.offsetHeight
if (windowedge-dropmenuobj.y < dropmenuobj.contentmeasure)
edgeoffset=dropmenuobj.contentmeasure+obj.offsetHeight
}
return edgeoffset
}

function tooltip_show(menucontents, obj, e, tipwidth){
if (window.event) event.cancelBubble=true
else if (e.stopPropagation) e.stopPropagation()
clearhidetip()
dropmenuobj=document.getElementById? document.getElementById("tooltip_div") : tooltip_div
dropmenuobj.innerHTML=menucontents
if (!dropmenuobj.repositioned){
document.body.appendChild(dropmenuobj)
dropmenuobj.repositioned = true
}

if (ie4||ns6){
showhide(dropmenuobj.style, e, "visible", "hidden", tipwidth)
dropmenuobj.x=getposOffset(obj, "left")
dropmenuobj.y=getposOffset(obj, "top")
dropmenuobj.style.left=dropmenuobj.x-clearbrowseredge(obj, "rightedge")+"px"
dropmenuobj.style.top=dropmenuobj.y-clearbrowseredge(obj, "bottomedge")+obj.offsetHeight+"px"
}
}

function hidetip(e){
if (typeof dropmenuobj!="undefined"){
if (ie4||ns6)
dropmenuobj.style.visibility="hidden"
}
}

function tooltip_hide(){
if (ie4||ns6)
// DE: CSP fix
// delayhide=setTimeout("hidetip()",disappeardelay)
delayhide=setTimeout(function() {hidetip();}, disappeardelay)
}

function clearhidetip(){
if (typeof delayhide!="undefined")
clearTimeout(delayhide)
}