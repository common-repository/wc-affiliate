<style type="text/css">
#wca-loader {
	background: #00000096;
	position: fixed;
	top: 0;
	height: 100vh;
	width: 100%;
	display: none;
	justify-content: center;
	align-items: center;
	z-index: 5000000000000;
}
#wca-loader.flex{
	display: flex;
}
#wca-loader .lds-ripple {
	display: inline-block;
	position: relative;
	width: 100px;
	height: 100px;
}
#wca-loader .lds-ripple div {
  position: absolute;
  border: 4px solid #fff;
  opacity: 1;
  border-radius: 50%;
  animation: lds-ripple 1s cubic-bezier(0, 0.2, 0.8, 1) infinite;
}
#wca-loader .lds-ripple div:nth-child(2) {
  animation-delay: -0.5s;
}
@keyframes lds-ripple {
  0% {
    top: 101px;
    left: 101px;
    width: 0;
    height: 0;
    opacity: 1;
  }
  100% {
    top: 0px;
    left: 0px;
    width: 202px;
    height: 202px;
    opacity: 0;
  }
}
#wf-alert-overlay {
  position: fixed;
  background: #00000096;
  top: 0;
  left: 0;
  height: 100%;
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}
#wf-alert-popup {
  background: #fff;
  border-radius: 4px;
  padding: 16px;
  width: 300px;
  position: relative;
  animation-name: message;
  animation-duration: 1s;
}
@keyframes message {  
  0% {transform: scale(0);}
  33% {transform: scale(1.2);}  
  66% {transform: scale(0.9);}  
  100% {transform: scale(1);}
}
#wf-alert-popup .wf-alert-dismiss {
  position: absolute;
  top: -10px;
  right: -10px;
  color: #c36;
  font-size: 23px;
  cursor: pointer;
  background: #fff;
  line-height: 0px;
  border-radius: 30px;
}
#wf-alert-popup .wf-alert-content {
  font-family: "DM Sans";
  font-size: 16px;
  font-weight: 400;
  line-height: 19px;
  text-align: center;
}
#wf-alert-popup.danger{
  background: #c36;
}
#wf-alert-popup.danger .wf-alert-content {
  color: #fff;
}

</style>
<div id="wca-loader">
	<div class="lds-ripple"><div></div><div></div></div>
</div>
<div id="wf-alert-overlay" style="display:none;">
  <div id="wf-alert-popup" class="">
    <span class="wf-alert-dismiss"><i class="fas fa-times-circle"></i></span>
    <div class="wf-alert-content"></div>
  </div>
</div>