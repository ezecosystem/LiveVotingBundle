function brain(options_){

    var options = options_;
    var source   = $("#presentation").html();
    var template = Handlebars.compile(source);
    Handlebars.registerPartial("comment", $("#comment-partial").html());
    var urlPath = options['URLS']['EVENT_STATUS']+getEventId(1);
    var globalState = null;
    var timeout = options['STATES']['PRE']['TIMEOUT'];
    var timer = new timer();
    var canIVote = true;
    var presentations = new presentationsArray();
    var shadow = $('<div id="shadow"></div>');
    var loader = $('#circleG');
    var footer = new footerClass($('#footer'));

    shadow.hide();
    Handlebars.registerHelper ('ifCond', function(v1, v2, options) {
        if (v1 == v2) {
            return options.fn(this);
        }
        return options.inverse(this);
    });


    $('body').append(shadow);

    showSpinner();
    $('body').on('change', '.vote-form', function(e){e.preventDefault();});


    $('body').on('click', '.vote-form input', function(e){
        e.preventDefault();
        if(!canIVote)return;
        var action = $(this).parent().parent().attr('action');
        var presentation_id = action.split('/').pop();
        var presentation = presentations.getById(presentation_id);
        var vote = $(this).attr('value');
        var rate = 'rate='+vote;
        if(presentation.getData()['votingEnabled']==true){
            showSpinner();
            $.ajax({
                type: 'post',
                'url': action,
                'data': rate,
                success: function(data){
                    presentation.highlightMe();
                    footer.displayMessage(data['errorMessage']);
                    presentation.setVote(vote);
                    hideSpinner();
                },
                error: function(e){
                    //fly out erro on footer
                    hideSpinner();
                }
            });
        }

    });


    /**
     *  Gets event id from url which looks like:
     *  /event/{id}
     */
    function getEventId(ret){
        var struct = window.location.pathname.split('/');
        // 2 because '/'.split('/') returns array len 2
        if(struct.length<=2)return ret.toString();
        return struct.pop();
    }

    var run = function() {
        $.getJSON(urlPath, function(data){

            switch(data['error']){
                case 1:
                    // displayMessageInFooter(data['errorMessage']);
                    footer.displayMessage(data['errorMessage']);
                break;
                case 2:
                    timeout = -1;
                    // displayMessageInFooter(data['errorMessage']);

                    footer.staticMessage(data['errorMessage']);
                    timer.stop();
                    return;
                break;
            }
            var state = data["eventStatus"];

            switch(state){
                case 'PRE':
                   footer.staticMessage(data['errorMessage']);
                   timeout = parseInt(options['STATES']['PRE']['TIMEOUT'])*1000;
                   break;
                case 'POST':
                    var seconds = parseInt(data['seconds']);
                    timeout = parseInt(options['STATES']['POST']['TIMEOUT'])*1000;
                    if(!timer.isRunning && seconds>0){
                        timer.init(parseInt(data['seconds']), changeFooter, endVoting);
                        timer.runTimer();
                    }
                    if(seconds<0){
                        timeout = -1;
                        endVoting(data['errorMessage']);
                    }
                case 'ACTIVE':
                    if(timeout>0){
                        timeout = parseInt(options['STATES'][state]['TIMEOUT'])*1000;
                    }
                    if(state!='POST'){
                        footer.removeStatic();
                    }
                    hideSpinner();
                    //add presentations
                    handleNewPresentations(data['presentations']);
                    break;
                default:
                    timeout = -1;

            }
            globalState = state;
            if(timeout>0) setTimeout(run, timeout);
        }); 
    }

    function handleNewPresentations(data){
        var pres; // single presentations
        for(var i in data){
            pres = data[i];
            presentations.add(pres);
        }
        presentations.notifiyAll();
    }

    function endVoting(message){
        //Thank u for voting
        canIVote = false;

        //footer.displayMessage(data['errorMessage']);
        //$("#footer .error").html("Voting is now closed.");

        presentations.setEnabledAll(false);
        footer.setStaticTimer('');
        footer.staticMessage(message);
    }

    function changeFooter(seconds_) {
        footer.setStaticTimer(seconds_.toString()+' seconds left until voting ends.');
    }

    /*
    Class timer which calls callbackEverySecond function each second
    and function callbackEnd when timer is done.
     */
    function timer(){
        this.isRunning = false;
        this.seconds = 1;
        this.callbackEverySecond;
        this.callbackEnd;
        var killMe = false;

        function stop(){
            killMe = true;
        }
        this.init = function(seconds_, callbackEverySecond_, callbackEnd_){
            this.seconds = seconds_;
            this.callbackEverySecond = callbackEverySecond_;
            this.callbackEnd = callbackEnd_;
            killme = false;
        }

        this.runTimer = function(){
            var that = this;
            this.isRunning = true;
            if(this.seconds==0 || killMe){
                this.callbackEnd();
                this.isRunning = false;
                return;
            }
            this.callbackEverySecond(this.seconds--);
            setTimeout(function(){that.runTimer()}, 1000);
        }
    }

    /*
    Class presentation that holds one presentation and pointer to html element
     */
    function presentationClass(){

        var data = null;
        var that = this;
        this.element = null;

        this.init = function(newData){
            this.setData(newData);
            this.element = $(template(data));
            this.element.find('.highLight').hide();
            this.element.find('.flash').hide();
            $("#voteScreen").append(this.element);
            this.element.find('.check').hide();
        }

        /*
        Returns true if user can now vote on it.
         */
        this.setData = function(newData){
            var status = false;
            if(data==null){
                status = newData['votingEnabled'];
            }else if(newData['votingEnabled']==true && data['votingEnabled']==false){
                status = true;
            }
            delete data;
            data = newData;
            return status;
        }

        this.getData = function(){
            return data;
        }
        this.setVote = function(vote_number){
            this.element.find('input[type=submit]').each(function(){
                if(this.value == vote_number){
                    $(this).addClass('active');
                }else{
                    $(this).removeClass('active');
                }
            });
        }


        this.setEnabled = function(enabled_status){
            if(!enabled_status) // enabled_status == false
                this.element.find('.highLight').fadeIn(2000);
            else
                this.element.find('.highLight').fadeOut(1000);
        }

        this.highlightMe = function(){
            this.setEnabled(true);
        }

        this.handle = function(){
            var vote = data['presenterRate'];
            this.setVote(vote);
            this.setEnabled(data['votingEnabled'] && canIVote);
        }
    }

    function presentationsArray(){
        var arr = {};
        var notify = [];
        /*
        Adds new presentation if it's not there and change it's view if needs.
         */
        this.add = function(presentation){
            var id = presentation['presentationId'].toString();
            if(arr[id] === undefined){
                arr[id] = new presentationClass();
                arr[id].init(presentation);
            }
            var status = arr[id].setData(presentation);
            if(status){
                notify.push(id);
            }
            arr[id].handle();
        }


        this.getById = function(id){
            return arr[id];
        }

        this.setEnabledAll = function(state){
            for(var i in arr){
                arr[i].setEnabled(false);
                arr[i].setVote(arr[i].getData()['presenterRate']);
            }
        }

        this.notifiyAll = function(){
            var scrolledTo = false;
            for(var i in notify){
                if(!scrolledTo){
                    $('html, body').animate({
                        scrollTop: arr[notify[i]].element.offset().top
                    }, 1000);
                    scrolledTo = true;
                }
                arr[notify[i]].highlightMe();
            }
            if(scrolledTo)
                footer.displayMessage('Please vote.');
            delete notify;
            notify = [];

        }
    }

    function footerClass(el){
        var element=el;
        var holdingUp = false;
        var that = this;
        var timeoutVariable = null;
        element.hide();

        this.displayMessage = function(message){
            setMessage(message);
            this.anim(3);

        }

        function setMessage(message){
            var er = element.find('.error');
            er.html(message);
        }

        function setTimer(tmrMsg){
            var tmr = element.find('.timer');
            tmr.html(tmrMsg);
        }
        this.anim = function(seconds){
            if(!holdingUp)
                this.animateUp(100);
            clearTimeout(timeoutVariable);
            timeoutVariable = setTimeout(
                function(){
                    if(!holdingUp)
                        that.animateDown(100);
                    setMessage('');
                }
            ,seconds*1000);
        }

        function endFooter(){
            setMessage('');
            setTimer('');
        }

        this.animateUp = function(value){
            element.show();
            element.animate({
                bottom: value+'px'
            }, 500);
        }

        this.animateDown = function(value){
            element.animate({
                bottom: -value+'px'
            }, 500, function(){endFooter();});
            element.show();
        }

        this.staticMessage = function(msg){
            setMessage(msg);
            holdOn();
        }

        this.setStaticTimer = function(timerMsg){
            setTimer(timerMsg);
            holdOn();
        }

        function holdOn(){
            if(holdingUp)return;
            holdingUp = true;
            that.animateUp(100);

        }

        this.removeStatic = function(){
            if(!holdingUp)return;
            holdingUp = false;
            this.animateDown(100);
            endFooter();
        }

    }

    function showSpinner(){
        shadow.show();
        loader.show();
    }
    function hideSpinner(){
        shadow.hide();
        loader.hide();
        $(".spin-loader").hide();
    }



    run();

}

