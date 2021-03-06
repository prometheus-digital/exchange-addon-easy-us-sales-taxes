/*
TaxCloud Cert Stuff
*/

var certObj;
jQuery(document).ready(function() {
	if (ajaxLoad) {
		load_jsonp_certs();
	} else {
		//	load_certs();
	}
});

var tcsURL = "taxcloud.net";
var tcsProtocol = (("https:" == document.location.protocol) ? "https:" : "http:");

function prepCert() {
	var canBuildCert = false;
	if (typeof certLink != 'undefined') {
		canBuildCert = true;
	} else {
		canBuildCert = false;
		alert("TaxCloud Exemption Certificate script cannot find JavaScript variable \"certLink\"\nThis should be the ID of the element which a customer will click to provide exemption details.")
	}
	if (typeof certListUrl != 'undefined') {
		canBuildCert = true;
	} else {
		canBuildCert = false;
		alert("TaxCloud Exemption Certificate script cannot find JavaScript variable \"certListUrl\"\nThis should be the URL on your server that will invoke the TaxCloud GetExemptCertificates API.")
	}
	if (typeof saveCertUrl != 'undefined') {
		canBuildCert = true;
	} else {
		canBuildCert = false;
		alert("TaxCloud Exemption Certificate script cannot find JavaScript variable \"saveCertUrl\"\nThis should be the URL on your server that will invoke the TaxCloud AddExemptCertificate API.")
	}
	if (typeof merchantNameForCert != 'undefined') {
		canBuildCert = true;
	} else {
		canBuildCert = false;
		alert("TaxCloud Exemption Certificate script cannot find JavaScript variable \"merchantNameForCert\"\nThis should your merchant/business name as you would like it to be display on the TaxCloud Exempt Certificate UI.")
	}
	if (typeof certSelectUrl != 'undefined') {
		canBuildCert = true;
	} else {
		canBuildCert = false;
		alert("TaxCloud Exemption Certificate script cannot find JavaScript variable \"certSelectUrl\"\nThis should be the URL on your server that will set the selected certificate ID to recalculate the cart totals..")
	}
	if (canBuildCert) {
		var tsCss = document.createElement('link');
		tsCss.type = 'text/css';
		tsCss.rel = 'stylesheet';
		tsCss.href = tcsProtocol + '//' + tcsURL + '/imgs/jquery-ui-1.8.7.taxcloud.css';
		var tccsss = document.getElementsByTagName('script')[0];
		tccsss.parentNode.insertBefore(tsCss, tccsss);
		var mStyle = document.createElement("style");
		var def = ".navlink{color:#0099FF;cursor:pointer;}.navlink:hover{color:#FF9900}#jqxmptlist{font-family:verdana;font-size:small}#jqxmpt{background:#FBFBFB url(" + tcsProtocol + "//" + tcsURL + "/imgs/states/None.gif) no-repeat center;font-family:verdana;font-size:small;width:750;}.irs{border:0px solid #999999;border-bottom-width:thin;}.tinput{background-color:transparent;border:1px solid #999999;}.tinput:hover{background-color:#ffffff;}.err{color:red;}";
		//def += ".fields{color:#000000;position:absolute;z-index:100;text-align:center;font-family: Garamond, Times New Roman;font-size:14pt;cursor:default;border:0px solid red;}.labels{color:#000000;position:absolute;z-index:100;text-align:left;font-family: Garamond, Times New Roman;font-size:14pt;cursor:default;border:0px solid red;}#CertNumber{top: 40px;left: 333px;width: 316px;font-size:10pt;font-family: Verdana, Arial;color:red;text-align:right;}#PurchaserName{top: 201px;left: 406px;width: 216px;}#PurchaserAddress{top: 225px;left: 189px;width: 437px;}#ExemptionState{top: 294px;left: 214px;width: 242px;}#ExemptionReason{top: 316px;left: 177px;width: 483px;height: 48px;}#SPOrderNumber{top: 350px;left: 448px;width: 257px;}#ExemptionCertDate{top: 376px;left: 448px;width: 257px;}#IDType{top: 405px;left: 448px;width: 257px;}#taxidNumber{top: 434px;left: 448px;width: 256px;}#BusinessType{top: 463px;left: 447px;width: 269px;}#Seller{top: 493px;left: 447px;width: 269px;}";
		mStyle.setAttribute("type", "text/css");
		if (mStyle.styleSheet) { // stupid IE
			mStyle.styleSheet.cssText = def;
		} else { //real browsers
			var myNode = document.createTextNode(def);
			mStyle.appendChild(myNode);
		}
		tccsss.parentNode.insertBefore(mStyle, tccsss);
		buildExemptCert();
		buildCertsList();
		buildDisplayCert();
	} else {
		alert('TaxCloud Exemption Certificate script failed to initialize.')
	}
}

function load_jsonp_certs() {
	//var url = TaxCloudTicUrl + "?format=jsonp"
	var url = certListUrl;
	//url += "&time=";
	//url += new Date().getTime().toString(); // prevent caching        
	var script = document.createElement("script");
	script.setAttribute("src", url);
	script.setAttribute("id", "certLister");
	script.setAttribute("type", "text/javascript");
	document.getElementsByTagName('script')[0].parentNode.appendChild(script);
}

function load_certs() {
	var certObject = new Object();
	certObject.cert_list = jQuery.parseJSON(certString);
	taxcloudCertificates(certObject);
}

function taxcloudCertificates(ptics) {
	var buildJQlink = '#' + certLink;
	jQuery(buildJQlink).unbind("click");
	certObj = ptics.cert_list
	if (certObj.length > 0) {
		jQuery(buildJQlink).click(function() {
			jQuery('#jqxmptlist').dialog('open');
		});
	} else {
		jQuery(buildJQlink).click(function() {
			jQuery('#jqxmpt').dialog('open');
		});
	}
	prepCert();
}

function useCert(which) {
	var hiddenField = "#" + hiddenCertificateField;
	jQuery.post(certSelectUrl, {
		certificateID: which
	}, function(data) {
		jQuery(hiddenField).val(which);
		jQuery('#jqxmptlist').dialog('close');
		if (withConfirm) {
			if (confirm("To update your order to recognize your selected certificate of exemption (" + which + ") we will need to reload. Would you like to do that now?")) {
				window.location.reload();
			}
		}
	});
}

function removeCert(which) {
	jQuery.post(certRemoveUrl, {
		certificateID: which
	}, function(data) {
		jQuery('#jqxmptlist').dialog('close');
		jQuery('#tcCertResult').html(data);
		jQuery('#jqxmptlist').empty();
		jQuery('#jqxmptlist').detach();
		if (ajaxLoad) {
			jQuery.getScript(certListUrl, function() {
				jQuery('#jqxmptlist').dialog('open');
			});
		} else {
			// reload page to refresh cert list
			window.location.reload();
		}
	});
}

function saveCert(which, exemptState, blanketPurchase, singlePurchaseOrderNumber, purchaserFirstName, purchaserAddress1, purchaserCity, purchaserState, purchaserZip, taxType, idNumber, purchaserBusinessType, purchaserExemptionReason, purchaserExemptionReasonValue) {
	var result = checkForm(which);
	if (result) {
		jQuery.post(saveCertUrl, {
			ExemptState: exemptState.attr('value'),
			SinglePurchaseOrderNumber: singlePurchaseOrderNumber.attr('value'),
			BlanketPurchase: blanketPurchase.attr('checked'),
			PurchaserFirstName: purchaserFirstName.attr('value'),
			PurchaserAddress1: purchaserAddress1.attr('value'),
			PurchaserCity: purchaserCity.attr('value'),
			PurchaserState: purchaserState.attr('value'),
			PurchaserZip: purchaserZip.attr('value'),
			TaxType: taxType.attr('value'),
			IDNumber: idNumber.attr('value'),
			StateOfIssue: exemptState.attr('value'),
			CountryOfIssue: 'US',
			PurchaserBusinessType: purchaserBusinessType.attr('value'),
			PurchaserExemptionReason: purchaserExemptionReason.attr('value'),
			PurchaserExemptionReasonValue: purchaserExemptionReasonValue.attr('value')
		});
	} else {
		return result;
	}
	if (!alert("Your exemption certificate has been saved and applied to the order.")) {
		if (!reloadWithSave) {
			jQuery('#jqxmptCert').dialog('close');
		} else {
			window.location.reload();
		}
	}
}

function pretty(keyWord) {
	switch (keyWord) {
	case "AccommodationAndFoodServices":
		keyWord = "Accommodation and Food Services";
		break;
	case "Agricultural_Forestry_Fishing_Hunting":
		keyWord = "Agricultural/Forestry/Fishing/Hunting";
		break;
	case "Construction":
		break;
	case "FinanceAndInsurance":
		keyWord = "Finance and Insurance";
		break;
	case "Information_PublishingAndCommunications":
		keyWord = "Information Publishing and Communications";
		break;
	case "Manufacturing":
		break;
	case "Mining":
		break;
	case "RealEstate":
		keyWord = "Real Estate";
		break;
	case "RentalAndLeasing":
		keyWord = "Rental and Leasing";
		break;
	case "RetailTrade":
		keyWord = "Retail Trade";
		break;
	case "TransportationAndWarehousing":
		keyWord = "Transportation and Warehousing";
		break;
	case "Utilities":
		break;
	case "WholesaleTrade":
		keyWord = "Wholesale Trade";
		break;
	case "BusinessServices":
		keyWord = "Business Services";
		break;
	case "ProfessionalServices":
		keyWord = "Professional Services";
		break;
	case "EducationAndHealthCareServices":
		keyWord = "Education and Health Care Services";
		break;
	case "NonprofitOrganization":
		keyWord = "Nonprofit Organization";
		break;
	case "Government":
		break;
	case "NotABusiness":
		keyWord = "Not a Business";
		break;
	case "FederalGovernmentDepartment":
		keyWord = "Federal Government Department";
		break;
	case "StateOrLocalGovernmentName":
		keyWord = "State or Local Government";
		break;
	case "TribalGovernmentName":
		keyWord = "Tribal Government";
		break;
	case "ForeignDiplomat":
		keyWord = "Foreign Diplomat";
		break;
	case "CharitableOrganization":
		keyWord = "Charitable Organization";
		break;
	case "ReligiousOrEducationalOrganization":
		keyWord = "Religious or Educational Organization";
		break;
	case "Resale":
		break;
	case "AgriculturalProduction":
		keyWord = "Agricultural Production";
		break;
	case "IndustrialProductionOrManufacturing":
		keyWord = "Industrial Production or Manufacturing";
		break;
	case "DirectPayPermit":
		keyWord = "Direct Pay Permit";
		break;
	case "DirectMail":
		keyWord = "Direct Mail";
		break;
	case "Other":
		break;
	case "DirectMail":
		keyWord = "Direct Mail";
		break;
	case "AL":
		keyWord = "Alabama";
		break;
	case "AK":
		keyWord = "Alaska";
		break;
	case "AZ":
		keyWord = "Arizona";
		break;
	case "AR":
		keyWord = "Arkansas";
		break;
	case "CA":
		keyWord = "California";
		break;
	case "CO":
		keyWord = "Colorado";
		break;
	case "CT":
		keyWord = "Connecticut";
		break;
	case "DE":
		keyWord = "Delaware";
		break;
	case "FL":
		keyWord = "Florida";
		break;
	case "GA":
		keyWord = "Georgia";
		break;
	case "HI":
		keyWord = "Hawaii";
		break;
	case "ID":
		keyWord = "Idaho";
		break;
	case "IL":
		keyWord = "Illinois";
		break;
	case "IN":
		keyWord = "Indiana";
		break;
	case "IA":
		keyWord = "Iowa";
		break;
	case "KS":
		keyWord = "Kansas";
		break;
	case "KY":
		keyWord = "Kentucky";
		break;
	case "LA":
		keyWord = "Louisiana";
		break;
	case "ME":
		keyWord = "Maine";
		break;
	case "MD":
		keyWord = "Maryland";
		break;
	case "MA":
		keyWord = "Massachusetts";
		break;
	case "MI":
		keyWord = "Michigan";
		break;
	case "MN":
		keyWord = "Minnesota";
		break;
	case "MS":
		keyWord = "Mississippi";
		break;
	case "MO":
		keyWord = "Missouri";
		break;
	case "MT":
		keyWord = "Montana";
		break;
	case "NE":
		keyWord = "Nebraska";
		break;
	case "NV":
		keyWord = "Nevada";
		break;
	case "NH":
		keyWord = "New Hampshire";
		break;
	case "NJ":
		keyWord = "New Jersey";
		break;
	case "NM":
		keyWord = "New Mexico";
		break;
	case "NY":
		keyWord = "New York";
		break;
	case "NC":
		keyWord = "North Carolina";
		break;
	case "ND":
		keyWord = "North Dakota";
		break;
	case "OH":
		keyWord = "Ohio";
		break;
	case "OK":
		keyWord = "Oklahoma";
		break;
	case "OR":
		keyWord = "Oregon";
		break;
	case "PA":
		keyWord = "Pennsylvania";
		break;
	case "RI":
		keyWord = "Rhode Island";
		break;
	case "SC":
		keyWord = "South Carolina";
		break;
	case "SD":
		keyWord = "South Dakota";
		break;
	case "TN":
		keyWord = "Tennessee";
		break;
	case "TX":
		keyWord = "Texas";
		break;
	case "UT":
		keyWord = "Utah";
		break;
	case "VT":
		keyWord = "Vermont";
		break;
	case "VA":
		keyWord = "Virginia";
		break;
	case "WA":
		keyWord = "Washington";
		break;
	case "DC":
		keyWord = "Washington DC";
		break;
	case "WV":
		keyWord = "West Virginia";
		break;
	case "WI":
		keyWord = "Wisconsin";
		break;
	case "WY":
		keyWord = "Wyoming";
		break;
	}
	return keyWord;
}

function certPopOpen(singleUse, certNumber, purchaserName, purchaserAddress, exemptionState, exemptionReason, exemptionCertDate, iDType, taxidNumber, businessType, sellerProp, certWatermark) {
	//alert(+"0 "+singleUse+"\n1 "+certNumber+"\n2 "+purchaserName+"\n3 "+purchaserAddress+"\n4 "+exemptionState+"\n5 "+exemptionReason+"\n6 "+exemptionCertDate+"\n7 "+iDType+"\n8 "+taxidNumber+"\n9 "+businessType+"\n10 "+sellerProp+"\n11 "+certWatermark);
	jQuery('#jqxmptCert').removeAttr("title");
	jQuery('#jqxmptCert').attr("title", "Exemption Certificate " + certNumber);
	jQuery('#ExemptionCertificate').removeAttr("title");;
	jQuery('#ExemptionCertificate').attr("title", "Exemption Certificate " + certNumber + " : " + exemptionCertDate);
	if (singleUse != 'false') {
		jQuery('#ExemptionCertificate').removeAttr("src");
		jQuery('#ExemptionCertificate').attr("src", tcsProtocol + "//" + tcsURL + "/imgs/cert/sp_exemption_certificate_750x600.png");
		jQuery("#SPOrderNumber").text(singleUse);
		jQuery("#SPOrderNumber").attr("title", singleUse);
	} else {
		jQuery('#ExemptionCertificate').removeAttr("src");
		jQuery('#ExemptionCertificate').attr("src", tcsProtocol + "//" + tcsURL + "/imgs/cert/exemption_certificate750x600.png");
		jQuery("#SPOrderNumber").text("");
		jQuery("#SPOrderNumber").attr("title", "");
	}
	jQuery("#CertNumber").text(certNumber);
	jQuery("#CertNumber").attr("title", certNumber);
	jQuery("#PurchaserName").text(purchaserName);
	jQuery("#PurchaserName").attr("title", purchaserName);
	jQuery("#PurchaserAddress").text(purchaserAddress);
	jQuery("#PurchaserAddress").attr("title", purchaserAddress);
	if (exemptionState.length > 36) {
		jQuery("#ExemptionState").attr("style", "font-size:small;top:301px;");
		jQuery("#ExemptionState").text(exemptionState.substring(0, 36) + "[...]");
	} else if (exemptionState.length > 18) {
		jQuery("#ExemptionState").attr("style", "font-size:small;top:301px;");
		jQuery("#ExemptionState").text(exemptionState);
	} else {
		jQuery("#ExemptionState").removeAttr("style");
		jQuery("#ExemptionState").text(exemptionState);
	}
	jQuery("#stateWatermark").removeAttr("style");
	jQuery("#stateWatermark").attr("style", "display:inline;position:absolute;z-index:0;left:0;top:0px;width:730px;height:580px;filter:;opacity:0.6;background:transparent url('" + tcsProtocol + "//" + tcsURL + "/imgs/states/" + certWatermark + ".gif') no-repeat center;");
	jQuery("#ExemptionState").attr("title", exemptionState);
	jQuery("#ExemptionReason").text(exemptionReason);
	jQuery("#ExemptionReason").attr("title", exemptionReason);
	jQuery("#ExemptionCertDate").text(exemptionCertDate);
	jQuery("#ExemptionCertDate").attr("title", exemptionCertDate);
	jQuery("#IDType").text(iDType);
	jQuery("#IDType").attr("title", iDType);
	jQuery("#taxidNumber").text(taxidNumber);
	jQuery("#taxidNumber").attr("title", taxidNumber);
	var tempBT = businessType;
	if (tempBT.length > 21) {
		tempBT = tempBT.substring(0, 21);
		tempBT += "..."
	}
	jQuery("#BusinessType").attr("title", businessType);
	jQuery("#BusinessType").text(tempBT);
	jQuery("#Seller").text(sellerProp);
	jQuery("#Seller").attr("title", sellerProp);
	jQuery('#jqxmptCert').dialog('open');
}

function buildDisplayCert() {
	var prettyCert = document.createElement('div');
	prettyCert.title = "Exemption Certificate";
	prettyCert.id = "jqxmptCert";
	prettyCert.setAttribute('style', 'display:none;');
	prettyCert.style.backgroundColor = "#E8F5E2";
	var classKeyword = "class";
	var certStyle = document.createElement("style");
	certStyle.setAttribute("type", "text/css");
	var def = ".fields{color:#000000;position:absolute;z-index:100;text-align:center;font-family: Garamond, Times New Roman;font-size:14pt;cursor:default;border:0px solid red;}.labels{color:#000000;position:absolute;z-index:100;text-align:left;font-family: Garamond, Times New Roman;font-size:14pt;cursor:default;border:0px solid red;}#CertNumber{top: 40px;left: 333px;width: 316px;font-size:10pt;font-family: Verdana, Arial;color:red;text-align:right;}#PurchaserName{top: 201px;left: 406px;width: 216px;}#PurchaserAddress{top: 225px;left: 189px;width: 437px;}#ExemptionState{top: 294px;left: 214px;width: 242px;}#ExemptionReason{top: 316px;left: 177px;width: 483px;height: 48px;}#SPOrderNumber{top: 350px;left: 448px;width: 257px;}#ExemptionCertDate{top: 376px;left: 448px;width: 257px;}#IDType{top: 405px;left: 448px;width: 257px;}#taxidNumber{top: 434px;left: 448px;width: 256px;}#BusinessType{top: 463px;left: 447px;width: 269px;}#Seller{top: 493px;left: 447px;width: 269px;}";
	if (certStyle.styleSheet) { //stupid IE
		if (jQuery.browser.msie) {
			if (jQuery.browser.version > 7) {} else {
				classKeyword = "className"
			}
		}
		certStyle.styleSheet.cssText = def;
	} else { //real browsers
		var myNode = document.createTextNode(def);
		certStyle.appendChild(myNode);
	}
	prettyCert.appendChild(certStyle);
	var watermarkImg = document.createElement("div");
	watermarkImg.id = "stateWatermark";
	//watermarkImg.setAttribute("style","position:absolute;z-index:-50;top:0px;width:750px;height:600px;background:transparent url('') no-repeat;");
	//IE needs these one by one
	watermarkImg.style.position = "absolute";
	watermarkImg.style.zIndex = "-50";
	watermarkImg.style.top = "0px";
	watermarkImg.style.width = "750";
	watermarkImg.style.height = "600";
	//watermarkImg.style.background="transparent url('') no-repeat;";
	watermarkImg.appendChild(document.createTextNode(" "));
	var certImg = document.createElement("img");
	certImg.id = "ExemptionCertificate";
	certImg.title = "Exemption Certificate XXXX";
	certImg.setAttribute("src", tcsProtocol + "//" + tcsURL + "/imgs/cert/exemption_certificate750x600.png");
	certImg.setAttribute("height", "600");
	certImg.setAttribute("width", "750");
	//certImg.setAttribute("style", "position:absolute;top:0px;left:0px;z-index:10;");
	//IE needs these one by one
	certImg.style.position = "absolute";
	certImg.style.top = 0;
	certImg.style.left = 0;
	certImg.style.zIndex = 10;
	prettyCert.appendChild(watermarkImg);
	prettyCert.appendChild(certImg);
	var certNumberProp = document.createElement("span");
	certNumberProp.id = "CertNumber";
	//certNumberProp.setAttribute("class","fields");
	certNumberProp.setAttribute(classKeyword, "fields");
	var purchaserNameProp = document.createElement("span");
	purchaserNameProp.id = "PurchaserName";
	purchaserNameProp.setAttribute(classKeyword, "fields");
	var purchaserAddressProp = document.createElement("span");
	purchaserAddressProp.id = "PurchaserAddress";
	purchaserAddressProp.setAttribute(classKeyword, "fields");
	var exemptionStateProp = document.createElement("span");
	exemptionStateProp.id = "ExemptionState";
	exemptionStateProp.setAttribute(classKeyword, "fields");
	var exemptionReasonProp = document.createElement("span");
	exemptionReasonProp.id = "ExemptionReason";
	exemptionReasonProp.setAttribute(classKeyword, "fields");
	var spOrderNumProp = document.createElement("span");
	spOrderNumProp.id = "SPOrderNumber";
	spOrderNumProp.setAttribute(classKeyword, "labels");
	var exemptionCertDateProp = document.createElement("span");
	exemptionCertDateProp.id = "ExemptionCertDate";
	exemptionCertDateProp.setAttribute(classKeyword, "labels");
	var iDTypeProp = document.createElement("span");
	iDTypeProp.id = "IDType";
	iDTypeProp.setAttribute(classKeyword, "labels");
	var iDNumberProp = document.createElement("span");
	iDNumberProp.id = "taxidNumber";
	iDNumberProp.setAttribute(classKeyword, "labels");
	var businessTypeProp = document.createElement("span");
	businessTypeProp.id = "BusinessType";
	businessTypeProp.setAttribute(classKeyword, "labels");
	var sellerProp = document.createElement("span");
	sellerProp.id = "Seller";
	sellerProp.setAttribute(classKeyword, "labels");
	prettyCert.appendChild(certNumberProp);
	prettyCert.appendChild(purchaserNameProp);
	prettyCert.appendChild(purchaserAddressProp);
	prettyCert.appendChild(exemptionStateProp);
	prettyCert.appendChild(exemptionReasonProp);
	prettyCert.appendChild(spOrderNumProp);
	prettyCert.appendChild(exemptionCertDateProp);
	prettyCert.appendChild(iDTypeProp);
	prettyCert.appendChild(iDNumberProp);
	prettyCert.appendChild(businessTypeProp);
	prettyCert.appendChild(sellerProp);
	var t = document.getElementsByTagName('span')[0];
	t.parentNode.insertBefore(prettyCert, t);
	jQuery("#jqxmptCert").dialog({
		autoOpen: false,
		width: 750,
		height: 645,
		modal: true
	});
}

function buildCertsList() {
	var list = document.createElement('div');
	list.title = "Select an Exemption Certificate or create a new one.";
	list.id = "jqxmptlist";
	list.setAttribute('style', 'display:none;');
	//var warnFieldset = document.createElement("fieldset");
	//var warnLegend = document.createElement("legend");
	//warnLegend.appendChild(document.createTextNode("Existing Certificates"));
	//warnFieldset.appendChild(warnLegend);
	var certsTable = document.createElement("table");
	//certsTable.setAttribute('style', 'width:100%;');
	certsTable.style.width = "100%";
	//certsTable.setAttribute('border', '1');
	var certsTBody = document.createElement("tbody");
	var existingDescTr = document.createElement("tr");
	var existingDescTd = document.createElement("td");
	existingDescTd.setAttribute("colSpan", "2"); //NOTE: IE is case sensative for this
	//moved below to get the cert count
	//var placeholder = document.createElement("strong").appendChild(document.createTextNode("You already have one or more Exemption Certificates on file with us. Please select an existing certificate, or create a new one."));
	//existingDescTd.appendChild(placeholder);
	existingDescTr.appendChild(existingDescTd);
	certsTBody.appendChild(existingDescTr);
	//new cert
	var certTr = document.createElement("tr");
	var certDiv = document.createElement("td");
	certDiv.setAttribute('valign', 'middle');
	certDiv.setAttribute('style', 'width:160px;');
	var certImage = document.createElement("img");
	certImage.title = "Create/register a new Exemption Certificate"
	certImage.setAttribute("src", tcsProtocol + "//" + tcsURL + "/imgs/cert/new_certificate150x120.png")
	certImage.setAttribute("style", "cursor:pointer;");
	//      certImage.setAttribute("onClick","jQuery('#jqxmptlist').dialog('close');jQuery('#jqxmpt').dialog('open');")
	//now for IE7
	certImage.onclick = function() {
		jQuery('#jqxmptlist').dialog('close');
		jQuery('#jqxmpt').dialog('open');
	};
	certImage.setAttribute("height", "120");
	certImage.setAttribute("width", "150");
	certImage.setAttribute("align", "left");
	certDiv.appendChild(certImage);
	certTr.appendChild(certDiv)
	var newTd = document.createElement("td");
	newTd.setAttribute('valign', 'middle');
	var newButton = document.createElement("input");
	newButton.setAttribute("type", "submit");
	newButton.setAttribute("class", "ui-state-default ui-priority-primary ui-corner-all");
	//stupid IE
	newButton.setAttribute("className", "ui-state-default ui-priority-primary ui-corner-all");
	newButton.setAttribute("style", "width:100%;cursor:pointer;");
	newButton.setAttribute("value", "Register a New Exemption Certificate");
	newButton.setAttribute("title", "Register a New Exemption Certificate");
	//       newButton.setAttribute("onClick","jQuery('#jqxmptlist').dialog('close');jQuery('#jqxmpt').dialog('open');")
	//now for IE7
	newButton.onclick = function() {
		jQuery('#jqxmptlist').dialog('close');
		jQuery('#jqxmpt').dialog('open')
	}; //to get IE to run an onclick on a dynamically generated element, we can't use setAttribute. Instead, we need to set the onclick property on the object with an anonymous function wrapping the code we want to run
	newTd.appendChild(newButton);
	certTr.appendChild(newTd);
	certsTBody.appendChild(certTr);
	//end new cert
	var myCertCount = 0;
	jQuery.each(certObj, function(i, item) {
		myCertCount++;
		var certTr = document.createElement("tr");
		certTr.id = "tr" + item.CertificateID;
		var certDiv = document.createElement("td");
		certDiv.setAttribute('valign', 'middle');
		certDiv.setAttribute('style', 'width:160px;');
		var certImage = document.createElement("img");
		certImage.title = "Use "
		if (item.ExemptionCertificateDetail.SinglePurchase == 'true') {
			certImage.setAttribute("src", tcsProtocol + "//" + tcsURL + "/imgs/cert/sp_exemption_certificate_150x120.png")
			certImage.title += "(re-issue) Single Purchase ";
		} else {
			certImage.setAttribute("src", tcsProtocol + "//" + tcsURL + "/imgs/cert/exemption_certificate150x120.png")
			certImage.title += "Blanket ";
		}
		certImage.title += "Exemption Certificate No. " + item.CertificateID;
		certImage.setAttribute("height", "120");
		certImage.setAttribute("width", "150");
		certImage.setAttribute("style", "cursor:pointer;");
		certDiv.appendChild(certImage);
		var detailsPanel = document.createElement("td");
		detailsPanel.setAttribute('valign', 'middle');
		detailsPanel.setAttribute('style', 'width:100%');
		detailsPanel.appendChild(document.createTextNode("Issued to: " + item.ExemptionCertificateDetail.PurchaserFirstName + " " + item.ExemptionCertificateDetail.PurchaserLastName));
		detailsPanel.appendChild(document.createElement("br"));
		var theCertStates = "";
		var certWatermark = "";
		var exStates = document.createTextNode("Exempt State(s): ");
		for (i = 0; i < item.ExemptionCertificateDetail.ArrayOfExemptStates.length; i++) {
			if (item.ExemptionCertificateDetail.ArrayOfExemptStates.length == 1) {
				exStates.appendData(pretty(item.ExemptionCertificateDetail.ArrayOfExemptStates[i].ExemptState));
				theCertStates += pretty(item.ExemptionCertificateDetail.ArrayOfExemptStates[i].ExemptState);
				certWatermark = item.ExemptionCertificateDetail.ArrayOfExemptStates[i].ExemptState;
			} else {
				exStates.appendData(item.ExemptionCertificateDetail.ArrayOfExemptStates[i].ExemptState);
				theCertStates += item.ExemptionCertificateDetail.ArrayOfExemptStates[i].ExemptState;
			}
			if (i < item.ExemptionCertificateDetail.ArrayOfExemptStates.length - 1) {
				exStates.appendData(", ");
				theCertStates += ", ";
			}
		}
		detailsPanel.appendChild(exStates)
		detailsPanel.appendChild(document.createElement("br"))
		detailsPanel.appendChild(document.createTextNode("Date: " + item.ExemptionCertificateDetail.DateEntered));
		detailsPanel.appendChild(document.createElement("br"))
		detailsPanel.appendChild(document.createTextNode("Purpose: " + pretty(item.ExemptionCertificateDetail.PurchaserExemptionReason)));
		detailsPanel.appendChild(document.createElement("br"))
		var remButton = document.createElement("input");
		remButton.setAttribute("type", "submit");
		remButton.setAttribute("style", "width:80px;cursor:pointer;");
		remButton.setAttribute("class", "ui-state-default ui-corner-all");
		//stupid IE
		remButton.setAttribute("className", "ui-state-default ui-corner-all");
		remButton.setAttribute("value", "Remove");
		remButton.setAttribute("title", "Remove/Revoke " + item.CertificateID);
		//      remButton.setAttribute("onClick", "if(confirm('Are you sure you want to remove this Exemption Certificate?')){removeCert('" + item.CertificateID + "')}");
		//for IE
		remButton.onclick = function() {
			if (confirm('Are you sure you want to remove this Exemption Certificate?')) {
				removeCert(item.CertificateID)
			}
		};
		var viewLink = document.createElement("input");
		viewLink.setAttribute("type", "submit");
		viewLink.setAttribute("style", "width:80px;cursor:pointer;");
		viewLink.setAttribute("class", "ui-state-default ui-corner-all");
		//stupid IE
		viewLink.setAttribute("className", "ui-state-default ui-corner-all");
		viewLink.setAttribute("value", "View");
		viewLink.setAttribute("title", "View " + item.CertificateID);
		var myAddress = item.ExemptionCertificateDetail.PurchaserAddress1;
		if (item.ExemptionCertificateDetail.PurchaserAddress2.length > 1) {
			myAddress += ", " + item.ExemptionCertificateDetail.PurchaserAddress2;
		}
		myAddress += ", " + item.ExemptionCertificateDetail.PurchaserCity;
		myAddress += ", " + item.ExemptionCertificateDetail.PurchaserState;
		myAddress += " " + item.ExemptionCertificateDetail.PurchaserZip;
		var purchExemptWhy = pretty(item.ExemptionCertificateDetail.PurchaserExemptionReason);
		if (item.ExemptionCertificateDetail.PurchaserExemptionReasonValue.length > 2) {
			purchExemptWhy += " : " + item.ExemptionCertificateDetail.PurchaserExemptionReasonValue;
		}
		var busType = pretty(item.ExemptionCertificateDetail.PurchaserBusinessType);
		if (busType == "Other") {
			busType = item.ExemptionCertificateDetail.PurchaserBusinessTypeOtherValue;
		}
		var spNumber = item.ExemptionCertificateDetail.SinglePurchase;
		if (spNumber == "true") {
			spNumber = item.ExemptionCertificateDetail.SinglePurchaseOrderNumber;
		}
		//       viewLink.setAttribute("onclick", "certPopOpen('" + spNumber + "','" + item.CertificateID + "','" + item.ExemptionCertificateDetail.PurchaserFirstName + " " + item.ExemptionCertificateDetail.PurchaserLastName + "','" + myAddress + "','" + theCertStates + "','" + purchExemptWhy + "','" + item.ExemptionCertificateDetail.DateEntered + "','" + item.ExemptionCertificateDetail.TaxIDType + "','" + item.ExemptionCertificateDetail.PurchaserTaxID + "','" + busType + "','"+merchantNameForCert+"','"+certWatermark+"')");
		//for IE
		viewLink.onclick = function() {
			certPopOpen(spNumber, item.CertificateID, item.ExemptionCertificateDetail.PurchaserFirstName + " " + item.ExemptionCertificateDetail.PurchaserLastName, myAddress, theCertStates, purchExemptWhy, item.ExemptionCertificateDetail.DateEntered, item.ExemptionCertificateDetail.TaxIDType, item.ExemptionCertificateDetail.PurchaserTaxID, busType, merchantNameForCert, certWatermark)
		};
		//	certImage.setAttribute("onclick", "certPopOpen('" + spNumber + "','" + item.CertificateID + "','" + item.ExemptionCertificateDetail.PurchaserFirstName + " " + item.ExemptionCertificateDetail.PurchaserLastName + "','" + myAddress + "','" + theCertStates + "','" + purchExemptWhy + "','" + item.ExemptionCertificateDetail.DateEntered + "','" + item.ExemptionCertificateDetail.TaxIDType + "','" + item.ExemptionCertificateDetail.PurchaserTaxID + "','" + busType + "','"+merchantNameForCert+"','"+certWatermark+"')");
		//for IE
		certImage.onclick = function() {
			certPopOpen(spNumber, item.CertificateID, item.ExemptionCertificateDetail.PurchaserFirstName + " " + item.ExemptionCertificateDetail.PurchaserLastName, myAddress, theCertStates, purchExemptWhy, item.ExemptionCertificateDetail.DateEntered, item.ExemptionCertificateDetail.TaxIDType, item.ExemptionCertificateDetail.PurchaserTaxID, busType, merchantNameForCert, certWatermark)
		};
		var useButton = document.createElement("input");
		useButton.setAttribute("type", "submit");
		useButton.setAttribute("style", "cursor:pointer;");
		useButton.setAttribute("value", "Use this Certificate");
		useButton.setAttribute("class", "ui-state-default ui-priority-primary ui-corner-all");
		//stupid IE
		useButton.setAttribute("className", "ui-state-default ui-priority-primary ui-corner-all");
		useButton.setAttribute("title", "Use Exemption Certificate " + item.CertificateID + " for this transaction");
		//       useButton.setAttribute("onClick", "useCert('" + item.CertificateID + "')");
		//for IE
		useButton.onclick = function() {
			useCert(item.CertificateID)
		};
		detailsPanel.appendChild(remButton);
		detailsPanel.appendChild(document.createTextNode(" "));
		detailsPanel.appendChild(viewLink);
		detailsPanel.appendChild(document.createTextNode(" "));
		detailsPanel.appendChild(useButton);
		certTr.appendChild(certDiv)
		certTr.appendChild(detailsPanel);
		certsTBody.appendChild(certTr);
		certsTable.appendChild(certsTBody);
	});
	var placeholder = document.createElement("b").appendChild(document.createTextNode("You have " + myCertCount + " Exemption Certificates on file with us. Please select an existing certificate below, or register a new one."));
	existingDescTd.appendChild(placeholder);
	//warnFieldset.appendChild(certsTable); 
	list.appendChild(certsTable);
	var t = document.getElementsByTagName('span')[0];
	t.parentNode.insertBefore(list, t);
	jQuery("#jqxmptlist").dialog({
		autoOpen: false,
		width: 550,
		height: 600,
		modal: true
	});
}

function buildExemptCert() {
	var xmpt = document.createElement('div');
	xmpt.title = "Purchaser Certificate of Exemption";
	xmpt.id = "jqxmpt";
	xmpt.setAttribute('style', 'display:none;');
	xmpt.style.backgroundColor = "#F7FBF4";
	var warnFieldset = document.createElement("fieldset");
	var warnLegend = document.createElement("legend");
	var stongLegend = document.createElement("strong");
	stongLegend.setAttribute("style", "color:#990000;");
	stongLegend.appendChild(document.createTextNode("Warning to Purchaser"));
	warnLegend.appendChild(stongLegend);
	warnFieldset.appendChild(warnLegend);
	var placeholder = document.createElement("strong");
	placeholder.appendChild(document.createTextNode("This is a multistate form. Not all states allow all exemptions listed on this form. "));
	var restText = document.createTextNode("Purchasers are responsible for knowing if they qualify to claim exemption from tax in the state that is due tax on this sale. The state that is due tax on this sale will be notified that you claimed exemption from sales tax. You will be held liable for any tax and interest, as well as civil and criminal penalties imposed by the member state, if you are not eligible to claim this exemption. Sellers may not accept a certificate of exemption for an entity-based exemption on a sale at a location operated by the seller within the designated state if the state does not allow such an entity-based exemption.");
	warnFieldset.appendChild(placeholder);
	warnFieldset.appendChild(restText);
	xmpt.appendChild(warnFieldset);
	var targFieldset = document.createElement("fieldset");
	var targLegend = document.createElement("legend");
	targLegend.appendChild(document.createTextNode("Certificate of Exemption"));
	targFieldset.appendChild(targLegend);
	var selectState = document.createElement("select");
	selectState.name = "ExemptState";
	selectState.id = "ExemptState";
	//stupid IE
	if (jQuery.browser.msie) { //ask JQuery if it is IE
		if (jQuery.browser.version < 8) {
			selectState.setAttribute("onchange", function() {
				xmpt.style.background = "url('" + tcsProtocol + "//" + tcsURL + "/imgs/states/" + this.value + ".gif')";
				xmpt.style.backgroundColor = "#F7FBF4";
				xmpt.style.backgroundRepeat = "no-repeat";
				xmpt.style.backgroundPosition = "center";
			});
		} else {
			selectState.setAttribute("onChange", "jQuery('#jqxmpt').css('background','#F7FBF4 url(" + tcsProtocol + "//" + tcsURL + "/imgs/states/'+this.value+'.gif) no-repeat center')");
		}
	} else {
		selectState.setAttribute("onChange", "jQuery('#jqxmpt').css('background','#F7FBF4 url(" + tcsProtocol + "//" + tcsURL + "/imgs/states/'+this.value+'.gif) no-repeat center')");
	}
	//selectState.onChange=function(){jQuery("#jqxmpt").css("background","#FBFBFB url("+tcsProtocol+"//"+tcsURL+"/imgs/states/"+this.value+".gif) no-repeat center")}; 
	selectState.title = "Select the state under whose laws you are claiming exemption.";
	selectState.setAttribute("class", "tinput");
	//stupid IE
	selectState.setAttribute("className", "tinput");
	addStates(selectState);
	targFieldset.appendChild(selectState);
	targFieldset.appendChild(document.createTextNode("Select the state under whose laws you are claiming exemption."));
	xmpt.appendChild(targFieldset);
	var myFieldset = document.createElement("fieldset");
	var myLegend = document.createElement("legend");
	myLegend.appendChild(document.createTextNode("Select one:"));
	myFieldset.appendChild(myLegend);
	xmpt.appendChild(myFieldset);
	var singPurchRadio = document.createElement("input");
	singPurchRadio.setAttribute("id", "SinglePurchase");
	singPurchRadio.setAttribute("name", "SinglePurchase");
	//	singPurchRadio.setAttribute("onClick", "clearAllErrors();jQuery('#BlanketPurchase').removeAttr('checked');jQuery('#blanketdesc').attr('style','display:none');jQuery('#SinglePurchaseOrderNumberDesc').attr('style','display:inline');jQuery('#SinglePurchaseOrderNumber').attr('style','display:inline'); jQuery('#SinglePurchaseOrderNumber').attr('name','SinglePurchaseOrderNumber'); jQuery('#SinglePurchaseOrderNumber').focus()"); 
	//now for IE
	singPurchRadio.onclick = function() {
		clearAllErrors();
		jQuery('#BlanketPurchase').removeAttr('checked');
		jQuery('#blanketdesc').attr('style', 'display:none');
		jQuery('#SinglePurchaseOrderNumberDesc').attr('style', 'display:inline');
		jQuery('#SinglePurchaseOrderNumber').attr('style', 'display:inline');
		jQuery('#SinglePurchaseOrderNumber').attr('name', 'SinglePurchaseOrderNumber');
		jQuery('#SinglePurchaseOrderNumber').focus()
	};
	singPurchRadio.setAttribute("type", "radio");
	singPurchRadio.setAttribute("class", "tinput");
	//stupid IE
	singPurchRadio.setAttribute("className", "tinput");
	myFieldset.appendChild(singPurchRadio);
	myFieldset.appendChild(document.createElement("b").appendChild(document.createTextNode("Single purchase certificate. ")));
	var myDesc = document.createElement("span");
	myDesc.id = "SinglePurchaseOrderNumberDesc";
	myDesc.setAttribute("style", "display:none;");
	myDesc.appendChild(document.createTextNode("Relates to invoice/purchase order \#"));
	myFieldset.appendChild(myDesc);
	var descInput = document.createElement("input");
	descInput.id = "SinglePurchaseOrderNumber";
	descInput.setAttribute("style", "display:none;");
	descInput.title = "Single Purchase Order Number";
	descInput.setAttribute("class", "tinput");;
	myFieldset.appendChild(descInput);
	myFieldset.appendChild(document.createElement("br"));
	var singPurchRadio2 = document.createElement("input");
	singPurchRadio2.setAttribute("checked", "true");
	singPurchRadio2.setAttribute("id", "BlanketPurchase");
	singPurchRadio2.setAttribute("name", "BlanketPurchase");
	singPurchRadio2.setAttribute("type", "radio");
	//	singPurchRadio2.setAttribute("onClick", "clearAllErrors();jQuery('#SinglePurchase').removeAttr('checked');jQuery('#blanketdesc').attr('style','display:inline');jQuery('#SinglePurchaseOrderNumberDesc').attr('style','display:none');jQuery('#SinglePurchaseOrderNumber').attr('style','display:none;')");
	//now for IE
	singPurchRadio2.onclick = function() {
		clearAllErrors();
		jQuery('#SinglePurchase').removeAttr('checked');
		jQuery('#blanketdesc').attr('style', 'display:inline');
		jQuery('#SinglePurchaseOrderNumberDesc').attr('style', 'display:none');
		jQuery('#SinglePurchaseOrderNumber').attr('style', 'display:none;')
	};
	myFieldset.appendChild(singPurchRadio2);
	myFieldset.appendChild(document.createElement("b").appendChild(document.createTextNode("Blanket certificate. ")));
	myDesc = document.createElement("span");
	myDesc.id = "blanketdesc";
	myDesc.appendChild(document.createTextNode("If selected, this certificate continues in force until canceled by the purchaser."));
	myFieldset.appendChild(myDesc);
	var idFieldset = document.createElement("fieldset");
	idFieldset.id = "didFieldset";
	var idyLegend = document.createElement("legend");
	idyLegend.appendChild(document.createTextNode("Purchaser Identification"));
	idFieldset.appendChild(idyLegend);
	xmpt.appendChild(idFieldset);
	var idTable = document.createElement("table");
	var idTableBody = document.createElement("tbody");
	idTable.setAttribute("cellspacing", 0);
	idTable.setAttribute("width", "100%");
	var firstRow = document.createElement("tr");
	firstRow.appendChild(drawTextInput("PurchaserFirstName", "width:100%;", "Purchaser Name", 1));
	var secondRow = document.createElement("tr");
	secondRow.appendChild(drawTextInput("PurchaserAddress1", "width:100%;", "Business Address", 1));
	secondRow.appendChild(drawTextInput("PurchaserCity", "width:100%;", "City", 1));
	var stateCell = document.createElement("td");
	stateCell.setAttribute("class", "irs");
	stateCell.appendChild(document.createTextNode("State"));
	stateCell.appendChild(document.createElement("br"));
	var selectMyState = document.createElement("select");
	selectMyState.name = "PurchaserState";
	selectMyState.id = "PurchaserState";
	selectMyState.title = "Purchaser State.";
	selectMyState.setAttribute("class", "tinput");
	addStates(selectMyState);
	stateCell.appendChild(selectMyState);
	secondRow.appendChild(stateCell);
	secondRow.appendChild(drawTextInput("PurchaserZip", "width:70px;", "Zip code", 1));
	var thirdRow = document.createElement("tr");
	thirdRow.appendChild(drawSelectTextInput("IDNumber", "white-space:nowrap", "Purchaser's Exemption ID number", 1));
	var fourthRow = drawBTSelector();
	var fifthRow = drawTTSelector();
	idTable.appendChild(idTableBody);
	idTableBody.appendChild(firstRow);
	idTableBody.appendChild(secondRow);
	idTableBody.appendChild(thirdRow);
	idTableBody.appendChild(fourthRow);
	idTableBody.appendChild(fifthRow);
	idFieldset.appendChild(idTable);
	var submitButton = document.createElement("input");
	submitButton.setAttribute("type", "submit");
	submitButton.setAttribute("style", "width:100%;");
	submitButton.setAttribute("class", "ui-state-default ui-priority-primary ui-corner-all");
	//stupid IE
	submitButton.setAttribute("className", "ui-state-default ui-priority-primary ui-corner-all");
	submitButton.setAttribute("value", "Save this Exemption Certificate");
	//	submitButton.setAttribute("onclick", "saveCert(this, jQuery('#ExemptState'), jQuery('#BlanketPurchase'), jQuery('#SinglePurchaseOrderNumber'), jQuery('#PurchaserFirstName'), jQuery('#PurchaserAddress1'), jQuery('#PurchaserCity'), jQuery('#PurchaserState'), jQuery('#PurchaserZip'), jQuery('#TaxType'), jQuery('#IDNumber'), jQuery('#PurchaserBusinessType'), jQuery('#PurchaserExemptionReason'), jQuery('#PurchaserExemptionReasonValue') )"); 
	//now for IE
	submitButton.onclick = function() {
		saveCert(this, jQuery('#ExemptState'), jQuery('#BlanketPurchase'), jQuery('#SinglePurchaseOrderNumber'), jQuery('#PurchaserFirstName'), jQuery('#PurchaserAddress1'), jQuery('#PurchaserCity'), jQuery('#PurchaserState'), jQuery('#PurchaserZip'), jQuery('#TaxType'), jQuery('#IDNumber'), jQuery('#PurchaserBusinessType'), jQuery('#PurchaserExemptionReason'), jQuery('#PurchaserExemptionReasonValue'))
	};
	xmpt.appendChild(submitButton);
	var t = document.getElementsByTagName('span')[0];
	t.parentNode.insertBefore(xmpt, t);
	jQuery("#jqxmpt").dialog({
		autoOpen: false,
		width: 750,
		modal: true
	});;
	return xmpt;
}

function drawTextInput(theId, style, label, colspan) {
	var firstCell = document.createElement("td");
	firstCell.setAttribute("class", "irs");
	firstCell.setAttribute("colSpan", colspan);
	firstCell.appendChild(document.createTextNode(label))
	firstCell.appendChild(document.createElement("br"));
	var myInput = document.createElement("input");
	myInput.id = theId;
	myInput.setAttribute("style", style);
	myInput.setAttribute("class", "tinput");
	myInput.setAttribute("type", "text");
	myInput.setAttribute("name", theId);
	firstCell.appendChild(myInput);
	return firstCell;
}

function selectedTaxIDType(what) {
	clearAllErrors();
	jQuery('#stateIssued').attr("style", "display:none;");
	jQuery('#countryIssued').attr("style", "display:none;");
	switch (what) {
	case "StateIssued":
		jQuery('#stateIssued').attr("style", "display:inline;");
		break;
	case "ForeignDiplomat":
		jQuery('#countryIssued').attr("style", "display:inline;");
		break;
	default:
		break;
	}
	jQuery('#IDNumber').focus();
}

function drawSelectTextInput(theId, style, label) {
	var firstCell = document.createElement("td");
	firstCell.setAttribute("style", style);
	firstCell.setAttribute("class", "irs");
	firstCell.setAttribute("colSpan", 4);
	firstCell.appendChild(document.createTextNode(label))
	firstCell.appendChild(document.createElement("br"));
	var selectTaxType = document.createElement("select")
	selectTaxType.name = "TaxType";
	selectTaxType.id = "TaxType";
	selectTaxType.title = "Purchaser Exemption ID Type.";
	selectTaxType.setAttribute("class", "tinput");
	selectTaxType.setAttribute("onchange", "selectedTaxIDType(this.value)");
	selectTaxType.appendChild(drawOpt("None", "[ - Select Type - ]"));
	selectTaxType.appendChild(drawOpt("FEIN", "Federal Employer ID"));
	selectTaxType.appendChild(drawOpt("StateIssued", "State Issued Exemption ID or Drivers License"));
	selectTaxType.appendChild(drawOpt("ForeignDiplomat", "Foreign Diplomat ID"));
	firstCell.appendChild(selectTaxType)
	firstCell.appendChild(document.createTextNode(" Number:"));
	var myInput = document.createElement("input");
	myInput.id = theId;
	myInput.setAttribute("name", theId);
	myInput.setAttribute("class", "tinput");;
	myInput.setAttribute("style", "width:90px")
	firstCell.appendChild(myInput);
	var stateIssuedSpan = document.createElement("span");
	stateIssuedSpan.setAttribute("id", "stateIssued");
	stateIssuedSpan.setAttribute("style", "display:none;");
	stateIssuedSpan.appendChild(document.createTextNode(" Issued by:"));
	var selectIssueState = document.createElement("select")
	selectIssueState.name = "StateOfIssue";
	selectIssueState.id = "StateOfIssue";
	selectIssueState.title = "Exemption ID State Of Issue.";
	selectIssueState.setAttribute("class", "tinput");;
	selectIssueState.setAttribute("style", "width:110px;");
	addStates(selectIssueState);
	stateIssuedSpan.appendChild(selectIssueState);
	var countryIssuedSpan = document.createElement("span");
	countryIssuedSpan.setAttribute("id", "countryIssued");
	countryIssuedSpan.setAttribute("style", "display:none;");
	countryIssuedSpan.appendChild(document.createTextNode(" Issued by:"));
	var countryInput = document.createElement("input");
	countryInput.name = "CountryOfIssue";
	countryInput.id = "CountryOfIssue";
	countryInput.title = "Exemption ID Country Of Issue.";
	countryInput.setAttribute("class", "tinput");
	countryInput.setAttribute("style", "width:70px;");
	countryIssuedSpan.appendChild(countryInput);
	firstCell.appendChild(stateIssuedSpan);
	firstCell.appendChild(countryIssuedSpan);
	return firstCell;
}

function popBT(what) {
	clearAllErrors();
	if (what == 'Other') {
		jQuery('#PurchaserBusinessTypeOtherValue').attr('style', 'display:inline;width:300px');
		jQuery('#PurchaserBusinessTypeOtherValue').focus()
		jQuery('#PurchaserBusinessTypeOtherValue').select()
	} else {
		jQuery('#PurchaserBusinessTypeOtherValue').attr('style', 'display:none;');
	}
}

function drawTTSelector() {
	var aRow = document.createElement("tr");
	var firstCell = document.createElement("td");
	firstCell.setAttribute("class", "irs");
	firstCell.setAttribute("colSpan", 1);
	firstCell.appendChild(document.createTextNode("Reason for exemption"))
	firstCell.appendChild(document.createElement("br"));
	var selectExemptType = document.createElement("select")
	selectExemptType.name = "PurchaserExemptionReason";
	selectExemptType.id = "PurchaserExemptionReason";
	selectExemptType.title = "Reason for exemption.";
	selectExemptType.setAttribute("class", "tinput");;
	selectExemptType.setAttribute("style", "width:100%;");
	selectExemptType.setAttribute("onchange", "checkReason(this.value)");
	selectExemptType.appendChild(drawOpt("None", "[ - Select - ]"));
	selectExemptType.appendChild(drawOpt("FederalGovernmentDepartment", "Federal Government Department"));
	selectExemptType.appendChild(drawOpt("StateOrLocalGovernment", "State Or Local Government"));
	selectExemptType.appendChild(drawOpt("TribalGovernmentName", "Tribal Government"));
	selectExemptType.appendChild(drawOpt("ForeignDiplomat", "Foreign Diplomat"));
	selectExemptType.appendChild(drawOpt("CharitableOrganization", "Charitable Organization"));
	selectExemptType.appendChild(drawOpt("ReligiousOrEducationalOrganization", "Religious or Educational Organization"));
	selectExemptType.appendChild(drawOpt("Resale", "Resale"));
	selectExemptType.appendChild(drawOpt("AgriculturalProduction", "Agricultural Production"));
	selectExemptType.appendChild(drawOpt("IndustrialProductionOrManufacturing", "Industrial Production or Manufacturing"));
	selectExemptType.appendChild(drawOpt("DirectPayPermit", "Direct Pay Permit"));
	selectExemptType.appendChild(drawOpt("DirectMail", "Direct Mail"));
	selectExemptType.appendChild(drawOpt("Other", "Other"));
	firstCell.appendChild(selectExemptType);
	var secondCell = document.createElement("td");
	secondCell.setAttribute("class", "irs");
	secondCell.setAttribute("colSpan", 3);
	var myInput = document.createElement("input");
	myInput.id = "PurchaserExemptionReasonValue";
	myInput.setAttribute("name", "PurchaserExemptionReasonValue");
	myInput.setAttribute("class", "tinput");
	myInput.setAttribute("style", "display:none;");
	secondCell.appendChild(document.createElement("br"));
	secondCell.appendChild(myInput);
	aRow.appendChild(firstCell);
	aRow.appendChild(secondCell);
	return aRow;
}

function drawBTSelector() {
	var aRow = document.createElement("tr");
	var firstCell = document.createElement("td");
	firstCell.setAttribute("class", "irs");
	firstCell.setAttribute("colSpan", 1);
	firstCell.appendChild(document.createTextNode("Purchaser Business Type"))
	firstCell.appendChild(document.createElement("br"));
	var selectTaxType = document.createElement("select")
	selectTaxType.name = "PurchaserBusinessType";
	selectTaxType.id = "PurchaserBusinessType";
	selectTaxType.title = "Purchaser Business Type.";
	selectTaxType.setAttribute("class", "tinput");;
	selectTaxType.setAttribute("style", "width:100%;");
	selectTaxType.setAttribute("onchange", "popBT(this.value)");
	selectTaxType.appendChild(drawOpt("None", "[ - Select - ]"));
	selectTaxType.appendChild(drawOpt("AccommodationAndFoodServices", "Accommodation And Food Services"));
	selectTaxType.appendChild(drawOpt("Agricultural_Forestry_Fishing_Hunting", "Agricultural/Forestry/Fishing/Hunting"));
	selectTaxType.appendChild(drawOpt("Construction", "Construction"));
	selectTaxType.appendChild(drawOpt("FinanceAndInsurance", "Finance or Insurance"));
	selectTaxType.appendChild(drawOpt("Information_PublishingAndCommunications", "Information Publishing and Communications"));
	selectTaxType.appendChild(drawOpt("Manufacturing", "Manufacturing"));
	selectTaxType.appendChild(drawOpt("Mining", "Mining"));
	selectTaxType.appendChild(drawOpt("RealEstate", "Real Estate"));
	selectTaxType.appendChild(drawOpt("RentalAndLeasing", "Rental and Leasing"));
	selectTaxType.appendChild(drawOpt("RetailTrade", "Retail Trade"));
	selectTaxType.appendChild(drawOpt("TransportationAndWarehousing", "Transportation and Warehousing"));
	selectTaxType.appendChild(drawOpt("Utilities", "Utilities"));
	selectTaxType.appendChild(drawOpt("WholesaleTrade", "Wholesale Trade"));
	selectTaxType.appendChild(drawOpt("BusinessServices", "Business Services"));
	selectTaxType.appendChild(drawOpt("ProfessionalServices", "Professional Services"));
	selectTaxType.appendChild(drawOpt("EducationAndHealthCareServices", "Education and Health Care Services"));
	selectTaxType.appendChild(drawOpt("NonprofitOrganization", "Nonprofit Organization"));
	selectTaxType.appendChild(drawOpt("Government", "Government"));
	selectTaxType.appendChild(drawOpt("NotABusiness", "Not a Business"));
	selectTaxType.appendChild(drawOpt("Other", "Other"));
	firstCell.appendChild(selectTaxType);
	var secondCell = document.createElement("td");
	secondCell.setAttribute("class", "irs");
	secondCell.setAttribute("colSpan", 3);
	var myInput = document.createElement("input");
	myInput.id = "PurchaserBusinessTypeOtherValue";
	myInput.setAttribute("class", "tinput");
	myInput.setAttribute("style", "display:none;");
	myInput.setAttribute("value", "Please explain");
	secondCell.appendChild(document.createElement("br"));
	secondCell.appendChild(myInput);
	aRow.appendChild(firstCell);
	aRow.appendChild(secondCell);
	return aRow;
}

function drawOpt(abrev, state) {
	var anOpt = document.createElement("option")
	anOpt.value = abrev;
	anOpt.appendChild(document.createTextNode(state))
	return anOpt;
}

function addStates(which) {
	which.appendChild(drawOpt("None", "[ - Select - ]"));
	which.appendChild(drawOpt("AL", "Alabama"));
	which.appendChild(drawOpt("AK", "Alaska"));
	which.appendChild(drawOpt("AZ", "Arizona"));
	which.appendChild(drawOpt("AR", "Arkansas"));
	which.appendChild(drawOpt("CA", "California"));
	which.appendChild(drawOpt("CO", "Colorado"));
	which.appendChild(drawOpt("CT", "Connecticut"));
	which.appendChild(drawOpt("DE", "Delaware"));
	which.appendChild(drawOpt("FL", "Florida"));
	which.appendChild(drawOpt("GA", "Georgia"));
	which.appendChild(drawOpt("HI", "Hawaii"));
	which.appendChild(drawOpt("ID", "Idaho"));
	which.appendChild(drawOpt("IL", "Illinois"));
	which.appendChild(drawOpt("IN", "Indiana"));
	which.appendChild(drawOpt("IA", "Iowa"));
	which.appendChild(drawOpt("KS", "Kansas"));
	which.appendChild(drawOpt("KY", "Kentucky"));
	which.appendChild(drawOpt("LA", "Louisiana"));
	which.appendChild(drawOpt("ME", "Maine"));
	which.appendChild(drawOpt("MD", "Maryland"));
	which.appendChild(drawOpt("MA", "Massachusetts"));
	which.appendChild(drawOpt("MI", "Michigan"));
	which.appendChild(drawOpt("MN", "Minnesota"));
	which.appendChild(drawOpt("MS", "Mississippi"));
	which.appendChild(drawOpt("MO", "Missouri"));
	which.appendChild(drawOpt("MT", "Montana"));
	which.appendChild(drawOpt("NE", "Nebraska"));
	which.appendChild(drawOpt("NV", "Nevada"));
	which.appendChild(drawOpt("NH", "New Hampshire"));
	which.appendChild(drawOpt("NJ", "New Jersey"));
	which.appendChild(drawOpt("NM", "New Mexico"));
	which.appendChild(drawOpt("NY", "New York"));
	which.appendChild(drawOpt("NC", "North Carolina"));
	which.appendChild(drawOpt("ND", "North Dakota"));
	which.appendChild(drawOpt("OH", "Ohio"));
	which.appendChild(drawOpt("OK", "Oklahoma"));
	which.appendChild(drawOpt("OR", "Oregon"));
	which.appendChild(drawOpt("PA", "Pennsylvania"));
	which.appendChild(drawOpt("RI", "Rhode Island"));
	which.appendChild(drawOpt("SC", "South Carolina"));
	which.appendChild(drawOpt("SD", "South Dakota"));
	which.appendChild(drawOpt("TN", "Tennessee"));
	which.appendChild(drawOpt("TX", "Texas"));
	which.appendChild(drawOpt("UT", "Utah"));
	which.appendChild(drawOpt("VT", "Vermont"));
	which.appendChild(drawOpt("VA", "Virginia"));
	which.appendChild(drawOpt("WA", "Washington"));
	which.appendChild(drawOpt("DC", "Washington DC"));
	which.appendChild(drawOpt("WV", "West Virginia"));
	which.appendChild(drawOpt("WI", "Wisconsin"));
	which.appendChild(drawOpt("WY", "Wyoming"));
}

function checkForm(form) {
	clearAllErrors();
	if (verifyField("ExemptState", "select")) {
		var passSingle = false;
		if (jQuery('#SinglePurchase').is(":checked")) {
			passSingle = verifyField("SinglePurchaseOrderNumber", "string")
		} else {
			passSingle = false;
		}
		if (passSingle = false) {
			return false;
		}
		if (verifyField("PurchaserFirstName", "name")) {
			if (verifyField("PurchaserAddress1", "string")) {
				if (verifyField("PurchaserCity", "string")) {
					if (verifyField("PurchaserState", "select")) {
						if (verifyField("PurchaserZip", "zip")) {
							if (verifyField("TaxType", "select")) {
								var completedTaxType = false;
								if (jQuery('#TaxType').val() == 'StateIssued') {
									completedTaxType = verifyField("StateOfIssue", "select");
									if (completedTaxType) {
										completedTaxType = verifyField("IDNumber", "string")
									}
								}
								if (jQuery('#TaxType').val() == 'ForeignDiplomat') {
									completedTaxType = verifyField("CountryOfIssue", "string");
									if (completedTaxType) {
										completedTaxType = verifyField("IDNumber", "string")
									}
								}
								if ((jQuery('#TaxType').val() == 'FEIN')) {
									completedTaxType = verifyField("IDNumber", "taxid")
								}
								if (completedTaxType) {
									if (verifyField("PurchaserBusinessType", "select")) {
										var nextCompleted = false;
										if (jQuery('#PurchaserBusinessType').val() == 'Other') {
											nextCompleted = jQuery('#PurchaserBusinessTypeOtherValue').val();
										} else {
											nextCompleted = true;
										}
										if (nextCompleted) {
											if (verifyField("PurchaserExemptionReason", "select")) {
												if (verifyField("PurchaserExemptionReasonValue", "string")) {
													return true;
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}
	return false;
}

function checkReason(input) {
	jQuery('#PurchaserExemptionReasonValue').attr("style", "display:none;");
	switch (input) {
	case 'Other':
		jQuery('#PurchaserExemptionReasonValue').attr("value", "Please explain");
		break;
	case 'FederalGovernmentDepartment':
		jQuery('#PurchaserExemptionReasonValue').attr("value", "Department name");
		break;
	case 'StateOrLocalGovernment':
		jQuery('#PurchaserExemptionReasonValue').attr("value", "Government name");
		break;
	case 'TribalGovernmentName':
		jQuery('#PurchaserExemptionReasonValue').attr("value", "Tribal name");
		break;
	case 'ForeignDiplomat':
		jQuery('#PurchaserExemptionReasonValue').attr("value", "Foreign Diplomat ID");
		break;
	case 'CharitableOrganization':
		jQuery('#PurchaserExemptionReasonValue').attr("value", "Charitable Organization ID");
		break;
	case 'ReligiousOrEducationalOrganization':
		jQuery('#PurchaserExemptionReasonValue').attr("value", "Organization ID");
		break;
	case 'Resale':
		jQuery('#PurchaserExemptionReasonValue').attr("value", "Resale ID");
		break;
	case 'AgriculturalProduction':
		jQuery('#PurchaserExemptionReasonValue').attr("value", "Agricultural Production ID");
		break;
	case 'IndustrialProductionOrManufacturing':
		jQuery('#PurchaserExemptionReasonValue').attr("value", "Production/Manufacturing ID");
		break;
	case 'DirectPayPermit':
		jQuery('#PurchaserExemptionReasonValue').attr("value", "Direct Pay Permit ID");
		break;
	case 'DirectMail':
		jQuery('#PurchaserExemptionReasonValue').attr("value", "Direct Mail ID");
		break;
	}
	if (input != 'None') {
		jQuery('#PurchaserExemptionReasonValue').attr("style", "display:inline;width:300px");
		jQuery('#PurchaserExemptionReasonValue').select();
		jQuery('#PurchaserExemptionReasonValue').focus();
	}
}

function verifyField(which, type) {
	var retBool = false;
	var findStr = "#" + which;
	clearErrors(findStr);
	if (checkForilleagals(findStr)) {
		switch (type) {
		case "name":
			if (jQuery(findStr).val().length < 3) {
				writeInputErr("Your name appears suspiciously short.", findStr);
			} else {
				retBool = true;
			}
			break;
		case "zip":
			if (!validateZipCode(jQuery(findStr).val())) {
				writeInputErr("Invalid zip code", findStr);
			} else {
				retBool = true;
			}
			break;
		case "select":
			if (jQuery(findStr).val() == 'None') {
				writeInputErr("Selection required.", findStr);
			} else {
				retBool = true;
			}
			break;
		case "taxid":
			if (!validateEID(jQuery(findStr).val())) {
				writeInputErr("Invalid Federal EIN (XX-XXXXXXX)", findStr);
			} else {
				retBool = true;
			}
			break;
		case "string":
			if (!validateString(jQuery(findStr).val())) {
				writeInputErr("Missing", findStr);
			} else {
				retBool = true;
			}
			break;
		default:
			alert("Validation not set up for " + type + " yet.");
			break;
		}
	}
	return retBool;
}

function checkForilleagals(which) {
	var retBool = false;
	if ((jQuery(which).val().indexOf("<") == -1) && (jQuery(which).val().indexOf(">") == -1) && (jQuery(which).val().indexOf("(") == -1) && (jQuery(which).val().indexOf(")") == -1) && (jQuery(which).val().indexOf("*") == -1) && (jQuery(which).val().indexOf("\\") == -1) && (jQuery(which).val().indexOf("/") == -1) && (jQuery(which).val().indexOf("\"") == -1)) {
		retBool = true;
	} else {
		writeInputErr("Invalid", which);
	}
	return retBool;
}

function clearAllErrors() {
	jQuery('.err').empty();
}

function clearErrors(which) {
	jQuery(which).css("border", "")
	jQuery(which).parent().children('.err').empty();
}

function writeInputErr(msg, which) {
	jQuery(which).parent().children('.err').empty();
	jQuery(which).css("border", "1px solid #ff9900");
	jQuery(which).parent().append("<b class='err'>" + msg + "</b>");
	jQuery(which).focus(function() {
		this.select()
	});
	jQuery(which).focus();
}

function validateString(elementValue) {
	if (elementValue.length < 2) {
		return false;
	} else {
		return true;
	}
}

function validateEID(elementValue) {
	var EIDPattern = /^[1-9]\d?-\d{7}jQuery/;
	return EIDPattern.test(elementValue);
}

function validateZipCode(elementValue) {
	var zipCodePattern = /^\d{5}jQuery|^\d{5}-\d{4}jQuery/;
	return zipCodePattern.test(elementValue);
}