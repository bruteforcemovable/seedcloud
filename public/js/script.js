var REFRESH_STATE_INTERVAL = 1000;

function resetfc() {
    var currentTask = localStorage.getItem('taskId');
    jQuery.ajax({
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'reset-fc',
            taskId: localStorage.getItem('taskId')
        },
        success: function (data) {
            if (currentTask === localStorage.getItem('taskId')) {
                setTimeout(checkStatusForCurrentTask, 500);
            }
        }
    });
}

function base64ArrayBuffer(arrayBuffer) {
    var base64    = '';
    var encodings = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';

    var bytes         = new Uint8Array(arrayBuffer);
    var byteLength    = bytes.byteLength;
    var byteRemainder = byteLength % 3;
    var mainLength    = byteLength - byteRemainder;

    var a, b, c, d;
    var chunk;

    // Main loop deals with bytes in chunks of 3
    for (var i = 0; i < mainLength; i = i + 3) {
        // Combine the three bytes into a single integer
        chunk = (bytes[i] << 16) | (bytes[i + 1] << 8) | bytes[i + 2];

        // Use bitmasks to extract 6-bit segments from the triplet
        a = (chunk & 16515072) >> 18; // 16515072 = (2^6 - 1) << 18
        b = (chunk & 258048)   >> 12; // 258048   = (2^6 - 1) << 12
        c = (chunk & 4032)     >>  6; // 4032     = (2^6 - 1) << 6
        d = chunk & 63;               // 63       = 2^6 - 1

        // Convert the raw binary segments to the appropriate ASCII encoding
        base64 += encodings[a] + encodings[b] + encodings[c] + encodings[d];
    }

    // Deal with the remaining bytes and padding
    if (byteRemainder == 1) {
        chunk = bytes[mainLength];

        a = (chunk & 252) >> 2; // 252 = (2^6 - 1) << 2

        // Set the 4 least significant bits to zero
        b = (chunk & 3)   << 4; // 3   = 2^2 - 1

        base64 += encodings[a] + encodings[b] + '==';
    } else if (byteRemainder == 2) {
        chunk = (bytes[mainLength] << 8) | bytes[mainLength + 1];

        a = (chunk & 64512) >> 10; // 64512 = (2^6 - 1) << 10
        b = (chunk & 1008)  >>  4; // 1008  = (2^6 - 1) << 4

        // Set the 2 least significant bits to zero
        c = (chunk & 15)    <<  2; // 15    = 2^4 - 1

        base64 += encodings[a] + encodings[b] + encodings[c] + '=';
    }

    return base64;
}

function processMovablePart1() {
    var fileInput = document.getElementById("p1file");
    var fileList = fileInput.files;
    if (fileList.length === 1 && fileList[0].size === 0x1000) {
        var file = fileInput.files[0];
        var fileReader = new FileReader();
        fileReader.readAsArrayBuffer(file);
        fileReader.addEventListener("loadend", function (){
            let arrayBuffer = fileReader.result
            let lfcsBuffer = arrayBuffer.slice(0, 8)
            let lfcsArray = new Uint8Array(lfcsBuffer)
            let textDecoder = new TextDecoder()
            let lfcsString = textDecoder.decode(lfcsArray)
            if (lfcsString == textDecoder.decode(new Uint8Array(8))) {
                alert("movable_part1.sed is invalid")
                return
            }
            document.getElementById("part1b64").value = base64ArrayBuffer(lfcsBuffer)
            let id0Buffer = arrayBuffer.slice(0x10, 0x10+32)
            let id0Array = new Uint8Array(id0Buffer)
            document.getElementById("friendCode").disabled = true
            document.getElementById("friendCode").value = "movable_part1 provided"
            let id0String = textDecoder.decode(id0Array)
            console.log(id0String,  btoa(id0String), id0String.length)
            if (btoa(id0String) != "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=") { // non blank, if id0 is injected with seedminer_helper
                let id0Input = document.getElementById("id0")
                id0Input.disabled = true
                id0Input.value = id0String
            }
        })
    }
}

/*
* A JavaScript implementation of the Secure Hash Algorithm, SHA-1, as defined
* in FIPS PUB 180-1
* Version 2.1a Copyright Paul Johnston 2000 - 2002.
* Other contributors: Greg Holt, Andrew Kepert, Ydnar, Lostinet
* Distributed under the BSD License
* See http://pajhome.org.uk/crypt/md5 for details.
*/

/*
 * Configurable variables. You may need to tweak these to be compatible with
 * the server-side, but the defaults work in most cases.
 */
var chrsz   = 8;  /* bits per input character. 8 - ASCII; 16 - Unicode      */

/*
 * These are the functions you'll usually want to call
 * They take string arguments and return either hex or base-64 encoded strings
 */
function sha1encoded(s){return binb2hex(core_sha1(str2binb(unescape(s)),unescape(s).length * chrsz));}

/*
 * Calculate the SHA-1 of an array of big-endian words, and a bit length
 */
function core_sha1(x, len)
{
    /* append padding */
    x[len >> 5] |= 0x80 << (24 - len % 32);
    x[((len + 64 >> 9) << 4) + 15] = len;

    var w = Array(80);
    var a =  1732584193;
    var b = -271733879;
    var c = -1732584194;
    var d =  271733878;
    var e = -1009589776;

    for(var i = 0; i < x.length; i += 16)
    {
        var olda = a;
        var oldb = b;
        var oldc = c;
        var oldd = d;
        var olde = e;

        for(var j = 0; j < 80; j++)
        {
            if(j < 16) w[j] = x[i + j];
            else w[j] = rol(w[j-3] ^ w[j-8] ^ w[j-14] ^ w[j-16], 1);
            var t = safe_add(safe_add(rol(a, 5), sha1_ft(j, b, c, d)),
                safe_add(safe_add(e, w[j]), sha1_kt(j)));
            e = d;
            d = c;
            c = rol(b, 30);
            b = a;
            a = t;
        }

        a = safe_add(a, olda);
        b = safe_add(b, oldb);
        c = safe_add(c, oldc);
        d = safe_add(d, oldd);
        e = safe_add(e, olde);
    }
    return Array(a, b, c, d, e);

}

/*
 * Perform the appropriate triplet combination function for the current
 * iteration
 */
function sha1_ft(t, b, c, d)
{
    if(t < 20) return (b & c) | ((~b) & d);
    if(t < 40) return b ^ c ^ d;
    if(t < 60) return (b & c) | (b & d) | (c & d);
    return b ^ c ^ d;
}

/*
 * Determine the appropriate additive constant for the current iteration
 */
function sha1_kt(t)
{
    return (t < 20) ?  1518500249 : (t < 40) ?  1859775393 :
        (t < 60) ? -1894007588 : -899497514;
}

/*
 * Add integers, wrapping at 2^32. This uses 16-bit operations internally
 * to work around bugs in some JS interpreters.
 */
function safe_add(x, y)
{
    var lsw = (x & 0xFFFF) + (y & 0xFFFF);
    var msw = (x >> 16) + (y >> 16) + (lsw >> 16);
    return (msw << 16) | (lsw & 0xFFFF);
}

/*
 * Bitwise rotate a 32-bit number to the left.
 */
function rol(num, cnt)
{
    return (num << cnt) | (num >>> (32 - cnt));
}

/*
 * Convert an 8-bit or 16-bit string to an array of big-endian words
 * In 8-bit function, characters >255 have their hi-byte silently ignored.
 */
function str2binb(str)
{
    var bin = Array();
    var mask = (1 << chrsz) - 1;
    for(var i = 0; i < str.length * chrsz; i += chrsz)
        bin[i>>5] |= (str.charCodeAt(i / chrsz) & mask) << (32 - chrsz - i%32);
    return bin;
}

/*
 * Convert an array of big-endian words to a hex string.
 */
function binb2hex(binarray)
{
    var hex_tab = "0123456789abcdef";
    var str = "";
    for(var i = 0; i < binarray.length * 4; i++)
    {
        str += hex_tab.charAt((binarray[i>>2] >> ((3 - i%4)*8+4)) & 0xF) +
            hex_tab.charAt((binarray[i>>2] >> ((3 - i%4)*8  )) & 0xF);
    }
    return str;
}

function CheckFC(friendcode) {
    var fc=friendcode.replace(/[^0-9]/g,"");
    if (fc.length !== 12) {
        // FC must be 12 digits
        return false;
    }
    // friend code to int
    var str_ifc=(parseInt(fc,10)).toString(16);
    for (var i=str_ifc.length;i<10;i++) {
        str_ifc="0"+str_ifc;
    }
    var le_str="%"+str_ifc.substr(8,2)+"%"+str_ifc.substr(6,2)+"%"+str_ifc.substr(4,2)+"%"+str_ifc.substr(2,2);
    var fc_check=str_ifc.substr(0,2);
    var sha_check = (parseInt(sha1encoded(le_str).substr(0,2),16)>>>1).toString(16);
    if (sha_check.length === 1) {
        sha_check = "0"+sha_check;
    }
    return (sha_check.toLowerCase() == fc_check.toLowerCase());
}

var STEP_INITIAL = 1;
var STEP_WAITING_FOR_PART1_DUMPER = 2;
var STEP_ADD_THE_BOT = 3;
var STEP_BRUTEFORCE_CHOOSE = 4;
var STEP_BRUTEFORCING = 5;
var STEP_MOVABLE_DONE = 6;

jQuery(function () {
	
	function showUnmineableModal() {
		jQuery('#unmineableModal').modal();
	}
	window.showUnmineableModal = showUnmineableModal;
	
    function changeStep(wantedStep) {
        jQuery('#accordion button[data-toggle="collapse"]:not([data-target="#collapseOne"])').prop('disabled', true);
        jQuery('#accordion .collapse.show:not(#collapseOne)').removeClass('show');

        jQuery('.request-form-main').hide();
        document.getElementById("fcProgress").style.display = "none";
        if (wantedStep === STEP_INITIAL) {
            localStorage.removeItem('taskId');
            //jQuery('button[data-target="#collapseTwo"]').prop('disabled', false);
            document.getElementById("beginButton").disabled = false;
            //jQuery('#collapseTwo').addClass('show');
            jQuery('.request-form-main').show();
            document.getElementById("fcProgress").style.display = "none";
        } else if (wantedStep === STEP_BRUTEFORCING) {
            jQuery('#id0Fill').html(localStorage.getItem('id0'));
            jQuery('button[data-target="#collapseFour"]').prop('disabled', false);
            jQuery('#collapseFour').addClass('show');

            console.log('request status from server every 5 seconds');

            var currentTask = localStorage.getItem('taskId');
            function checkStatusForCurrentTask() {
                jQuery.ajax({
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get-state',
                        taskId: localStorage.getItem('taskId')
                    },
                    success: function (data) {
                        if (currentTask === localStorage.getItem('taskId')) {
                            var currentState = data.currentState * 1;

                            if (currentState === -1) {
                                showUnmineableModal();
                                changeStep(STEP_INITIAL);
                            }
                            if (currentState === -2) {
                                showUnmineableModal();
                                changeStep(STEP_INITIAL);
                            }

                            jQuery("#bfProgress").removeClass("bg-warning");

                            if (currentState === 4) {
                                jQuery("#bfProgress").addClass("bg-warning");
                                jQuery("#bfProgress").text("Bruteforcing...");
                            }
                            if (currentState === 5) {
                                changeStep(STEP_MOVABLE_DONE);
                            }

                            if (currentState === 3 || currentState === 4) {
                                setTimeout(checkStatusForCurrentTask, REFRESH_STATE_INTERVAL);
                            }
                        }
                    }
                });

            }
            checkStatusForCurrentTask();
        } else if (wantedStep === STEP_MOVABLE_DONE) {

            jQuery('button[data-target="#collapseFive"]').prop('disabled', false);
            jQuery('#collapseFive').addClass('show');

            jQuery('#downloadMovable').attr('href', '/get_movable?task=' + localStorage.getItem('taskId') );
            jQuery('#downloadMovable2').attr('href', '/get_movable?task=' + localStorage.getItem('taskId') );
        } else if (wantedStep === STEP_WAITING_FOR_PART1_DUMPER) {
            jQuery('.request-form-main').slideUp();
            document.getElementById("fcProgress").style.display = "block";

            console.log('request status from server every 5 seconds');

            var currentTask = localStorage.getItem('taskId');
            function checkStatusForCurrentTask() {
                jQuery.ajax({
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get-state',
                        taskId: localStorage.getItem('taskId')
                    },
                    success: function (data) {
                        if (currentTask === localStorage.getItem('taskId')) {
                            var currentState = data.currentState * 1;
							
							if (currentState === -1) {
								showUnmineableModal();
								changeStep(STEP_INITIAL);
							}
							if (currentState === -2) {
								showUnmineableModal();
								changeStep(STEP_INITIAL);
							}

							if (currentState === 0) {
                                setTimeout(checkStatusForCurrentTask, REFRESH_STATE_INTERVAL);
                            }
							if (currentState === 1 ) {
								//= Part1 Dumper Claimed
								localStorage.setItem('claimedBy', data.claimedBy)
								changeStep(STEP_ADD_THE_BOT);
							}
							if (currentState === 2 ) {
								//= Part1 Dumper Done
								changeStep(STEP_BRUTEFORCE_CHOOSE)
							}
							if (currentState === 4 || currentState === 3) {
								//= Bruteforcing or Wait for Bruteforce
								changeStep(STEP_BRUTEFORCING);
							}
							if (currentState === 5 ) {
								//= Movable.sed ready
								changeStep(STEP_MOVABLE_DONE);
							}
                        }
                    }
                });

            }
            checkStatusForCurrentTask();
        } else if (wantedStep === STEP_ADD_THE_BOT) {
            jQuery('button[data-target="#collapseTwo"]').prop('disabled', false);
            document.getElementById("collapseTwo").classList.add("show");
            var s = localStorage.getItem('claimedBy');
            var claimedBy = (s.substring(0,4) + "-" + s.substring(4,8) + "-" + s.substring(8,12));
            jQuery('.js-friendcode').html(claimedBy);
            console.log('request status from server every 5 seconds');

            var currentTask = localStorage.getItem('taskId');
            function checkStatusForCurrentTask() {
                jQuery.ajax({
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get-state',
                        taskId: localStorage.getItem('taskId')
                    },
                    success: function (data) {
                        if (currentTask === localStorage.getItem('taskId')) {
                            var currentState = data.currentState * 1;

							if (currentState === -1) {
								showUnmineableModal();
								changeStep(STEP_INITIAL);
							}
							if (currentState === -2) {
								showUnmineableModal();
								changeStep(STEP_INITIAL);
							}
							
                            if (currentState === 0) {
                                setTimeout(checkStatusForCurrentTask, REFRESH_STATE_INTERVAL);
                            }
							if (currentState === 1 ) {
								//= Part1 Dumper Claimed
								localStorage.setItem('claimedBy', data.claimedBy)
								changeStep(STEP_ADD_THE_BOT);
							}
							if (currentState === 2 ) {
								//= Part1 Dumper Done
								changeStep(STEP_BRUTEFORCE_CHOOSE)
							}
							if (currentState === 4 || currentState === 3) {
								//= Bruteforcing or Wait for Bruteforce
								changeStep(STEP_BRUTEFORCING);
							}
							if (currentState === 5 ) {
								//= Movable.sed ready
								changeStep(STEP_MOVABLE_DONE);
							}

                            if (data.timeout === "true" || data.timeout === true) {
                                jQuery('#collapseTwo .card-body').css('display', 'none');
                                jQuery('#collapseTwo .card-body.js-timeout').css('display', 'block');
                                jQuery('#collapseTwo .card-body.js-lockout').css('display', 'none');
                            } else if (data.lockout === "true" || data.lockout === true) {
                                jQuery('#collapseTwo .card-body').css('display', 'none');
                                jQuery('#collapseTwo .card-body.js-timeout').css('display', 'none');
                                jQuery('#collapseTwo .card-body.js-lockout').css('display', 'block');
                            } else {
                                jQuery('#collapseTwo .card-body').css('display', 'block');
                                jQuery('#collapseTwo .card-body.js-timeout').css('display', 'none');
                                jQuery('#collapseTwo .card-body.js-lockout').css('display', 'none');
                            }
                        }
                    }
                });

            }
            checkStatusForCurrentTask();
        } else if (wantedStep === STEP_BRUTEFORCE_CHOOSE){
            jQuery('button[data-target="#collapseThree"]').prop('disabled', false);
            document.getElementById("collapseThree").classList.add("show");
            document.getElementById("downloadPart1").href = "/getPart1?task=" + localStorage.getItem("taskId");
            jQuery('#continue').off('click').on('click', function () {
                jQuery.ajax({
                    type: 'POST',
                    data: {
                        action: 'do-bruteforce',
                        taskId: localStorage.getItem('taskId')
                    },
                    success: function (data) {
                        changeStep(STEP_BRUTEFORCING);
                    }
                });
            });
        }
    }

    if (localStorage.getItem('taskId')) {
        var currentTask = localStorage.getItem('taskId');
        jQuery.ajax({
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get-state',
                taskId: localStorage.getItem('taskId')
            },
            success: function (data) {
                if (currentTask === localStorage.getItem('taskId')) {
                    var currentState = data.currentState;

                    if (currentState === -1) {
                        showUnmineableModal();
                        changeStep(STEP_INITIAL);
                    }
                    if (currentState === -2) {
                        showUnmineableModal();
                        changeStep(STEP_INITIAL);
                    }

                    if (currentState === 0 ) {
                        //= Waitingfor Part1 Dumper
                        changeStep(STEP_WAITING_FOR_PART1_DUMPER);
                    }
                    if (currentState === 1 ) {
                        //= Part1 Dumper Claimed
                        localStorage.setItem('claimedBy', data.claimedBy)
                        changeStep(STEP_ADD_THE_BOT);
                    }
                    if (currentState === 2 ) {
                        //= Part1 Dumper Done
                        changeStep(STEP_BRUTEFORCE_CHOOSE)
                    }
                    if (currentState === 4 || currentState === 3) {
                        //= Bruteforcing or Wait for Bruteforce
                        changeStep(STEP_BRUTEFORCING);
                    }
                    if (currentState === 5 ) {
                        //= Movable.sed ready
                        changeStep(STEP_MOVABLE_DONE);
                    }
                }
            }
        });
        //@TODO: Instead of going to BRUTEFORCING this needs to get the state first from the server
    } else {
        changeStep(STEP_INITIAL);
    }

    jQuery(document).on('click', '#cancelButton3, #cancelButton, #cancelButton2, #cancelButton1', function () {
       changeStep(STEP_INITIAL);
    });
    jQuery(document).on('click', '#cancelButton3', function () {
       changeStep(STEP_INITIAL);
    });


    var beginButton = document.getElementById("beginButton");
    beginButton.addEventListener("click", function (e) {
        e.preventDefault();
        document.getElementById("beginButton").disabled = true;
        document.getElementById("id0").value = document.getElementById("id0").value.toLowerCase();
		
        if (document.getElementById("part1b64").value.length === 0) {
            processMovablePart1();
        }

        var id0Val = document.getElementById("id0").value.replace(/[^A-Za-z0-9]/g,"");
        document.getElementById("id0").value = id0Val;

        var part1b64Val = document.getElementById("part1b64").value;

        if (part1b64Val.length > 0 && id0Val.length === 32) {
            //Part1 Supplied, we can skip the bot phase
            localStorage.setItem("id0", id0Val);
            localStorage.setItem("part1b64", part1b64Val.value);

            jQuery.ajax({
                type: 'POST',
                dataType: 'json',
                data: {
                    id0: id0Val,
                    part1b64: part1b64Val
                },
                success: function (data) {
                    if (data.success === true) {
                        localStorage.setItem("taskId", data.taskId);
                        changeStep(STEP_BRUTEFORCING);
                    } else {
                        alert(data.message);
                    }
                }
            });
            return;
        }


        document.getElementById("friendCode").value = document.getElementById("friendCode").value.toLowerCase();
        var fcVal = document.getElementById("friendCode").value.replace(/[^0-9]/g,"");
        document.getElementById("friendCode").value = fcVal;

        if (!CheckFC(fcVal)) {
            alert('The FriendCode you supplied is not valid. Please make sure your input was correct.');
        }

        if (fcVal.length === 12 && id0Val.length === 32 && CheckFC(fcVal)) {
            //Part1 not supplied, send and wait for bot


            localStorage.setItem("id0", id0Val);

            jQuery.ajax({
                type: 'POST',
                dataType: 'json',
                data: {
                    id0: id0Val,
                    friendcode: fcVal
                },
                success: function (data) {
                    if (data.success === true) {
                        localStorage.setItem("taskId", data.taskId);
                        changeStep(STEP_WAITING_FOR_PART1_DUMPER);
                    } else {
                        alert(data.message);
                        changeStep(STEP_INITIAL);
                    }
                }
            });
            return;
        }
		
		if (id0Val.length > 0 && id0Val.length < 32) {
			alert("The id0 you provided is not of the correct length. Expected 32 Characters"); 
		}

    });

    jQuery('#uploadp1').on('click', function (e) {
        let fileInput = document.getElementById("p1file");
        fileInput.click();
        e.preventDefault();
    });
    jQuery('#p1file').on('change', function (e) {
        processMovablePart1();
        e.preventDefault();
    });
});
