// this needs to change in the future...
var newsEllipsizer = null;
var videoEllipsizer = null;

function getNewsStories() {
  return document.getElementById('newsStories').childNodes;
}

function getNewsDots() {
  return document.getElementById('newsPagerDots').childNodes;
}


function getVideos() {
  return document.getElementById('videos').childNodes;
}

function getVideoDots() {
  return document.getElementById('videoPagerDots').childNodes;
}

function moduleHandleWindowResize() {
  function getCSSValue(element, key) {
    if (window.getComputedStyle) {
      return document.defaultView.getComputedStyle(element, null).getPropertyValue(key);
        
    } else if (elelementem.currentStyle) {
      if (key == 'float') { 
        key = 'styleFloat'; 
      } else {
        var re = /(\-([a-z]){1})/g; // hyphens to camel case
        if (re.test(key)) {
          key = key.replace(re, function () {
            return arguments[2].toUpperCase();
          });
        }
      }
      return element.currentStyle[key] ? element.currentStyle[key] : null;
    }
    return '';
  }

  function getCSSHeight(element) {
    return element.offsetHeight
      - parseFloat(getCSSValue(element, 'border-top-width')) 
      - parseFloat(getCSSValue(element, 'border-bottom-width'))
      - parseFloat(getCSSValue(element, 'padding-top'))
      - parseFloat(getCSSValue(element, 'padding-bottom'));
  }

  var blocks = document.getElementById('fillscreen').childNodes;
  
  for (var i = 0; i < blocks.length; i++) {
    var blockborder = blocks[i].childNodes[0];
    if (!blockborder) { continue; }
      
    var clipHeight = getCSSHeight(blocks[i])
      - parseFloat(getCSSValue(blockborder, 'border-top-width')) 
      - parseFloat(getCSSValue(blockborder, 'border-bottom-width'))
      - parseFloat(getCSSValue(blockborder, 'padding-top'))
      - parseFloat(getCSSValue(blockborder, 'padding-bottom'))
      - parseFloat(getCSSValue(blockborder, 'margin-top'))
      - parseFloat(getCSSValue(blockborder, 'margin-bottom'));
    
    blockborder.style.height = clipHeight+'px';
    
    // If the block ends in a list, clip off items in the list so that 
    // we don't see partial items
    if (blockborder.childNodes.length < 2) { continue; }
    var blockheader = blockborder.childNodes[0];
    var blockcontent = blockborder.childNodes[1];
    
    // How big can the content be?
    var contentClipHeight = clipHeight 
      - blockheader.offsetHeight
      - parseFloat(getCSSValue(blockheader, 'margin-top'))
      - parseFloat(getCSSValue(blockheader, 'margin-bottom'))
      - parseFloat(getCSSValue(blockheader, 'border-top-width'))
      - parseFloat(getCSSValue(blockheader, 'border-bottom-width'))
      - parseFloat(getCSSValue(blockcontent, 'border-top-width')) 
      - parseFloat(getCSSValue(blockcontent, 'border-bottom-width'))
      - parseFloat(getCSSValue(blockcontent, 'padding-top'))
      - parseFloat(getCSSValue(blockcontent, 'padding-bottom'))
      - parseFloat(getCSSValue(blockcontent, 'margin-top'))
      - parseFloat(getCSSValue(blockcontent, 'margin-bottom'));

    if (!blockcontent.childNodes.length) { continue; }
    var last = blockcontent.childNodes[blockcontent.childNodes.length - 1];
    
    blockcontent.style.height = 'auto';
    
    if (last.nodeName == 'UL') {
      var listItems = last.childNodes;
      for (var j = 0; j < listItems.length; j++) {
        listItems[j].style.display = 'list-item'; // make all list items visible
      }
  
      var k = listItems.length - 1;
      while (getCSSHeight(blockcontent) > contentClipHeight) {
        listItems[k].style.display = 'none';
        if (--k < 0) { break; } // hid everything, stop
      }
    }

    blockcontent.style.height = contentClipHeight+'px'; // set block content height
  }
  
  // set the size on the news stories
  var stories = getNewsStories();
  if (stories.length) {
    var pager = document.getElementById('newsPager');
    var storyClipHeight = getCSSHeight(document.getElementById('newsStories'))
      - pager.offsetHeight
      - parseFloat(getCSSValue(stories[0], 'border-top-width')) 
      - parseFloat(getCSSValue(stories[0], 'border-bottom-width'))
      - parseFloat(getCSSValue(stories[0], 'padding-top'))
      - parseFloat(getCSSValue(stories[0], 'padding-bottom'))
      - parseFloat(getCSSValue(stories[0], 'margin-top'))
      - parseFloat(getCSSValue(stories[0], 'margin-bottom'));
      
    for (var i = 0; i < stories.length; i++) {
      stories[i].style.height = storyClipHeight+'px';
    }
  }
  
  if (newsEllipsizer == null) {
    newsEllipsizer = new ellipsizer({refreshOnResize: false});
    newsEllipsizer.addElements(getNewsStories());
  } else {
    setTimeout(function () {
      newsEllipsizer.refresh();
    }, 1);
  }

  // set the size on the videos
  var videos = getVideos();
  if (videos.length) {
    var pager = document.getElementById('videoPager');
    var videoClipHeight = getCSSHeight(document.getElementById('videos'))
      - pager.offsetHeight
      - parseFloat(getCSSValue(videos[0], 'border-top-width')) 
      - parseFloat(getCSSValue(videos[0], 'border-bottom-width'))
      - parseFloat(getCSSValue(videos[0], 'padding-top'))
      - parseFloat(getCSSValue(videos[0], 'padding-bottom'))
      - parseFloat(getCSSValue(videos[0], 'margin-top'))
      - parseFloat(getCSSValue(videos[0], 'margin-bottom'));
      
    for (var i = 0; i < videos.length; i++) {
      videos[i].style.height = videoClipHeight+'px';
    }
  }
  
  if (videoEllipsizer == null) {
    videoEllipsizer = new ellipsizer({refreshOnResize: false});
    videoEllipsizer.addElements(getVideos());
  } else {
    setTimeout(function () {
      videoEllipsizer.refresh();
    }, 1);
  }
}

function newsPaneSwitchStory(elem, direction) {
  if (elem.className.match(/disabled/)) { return false; }

  var stories = getNewsStories();
  
  var dots = getNewsDots();
  var prev = document.getElementById('newsStoryPrev');
  var next = document.getElementById('newsStoryNext');
  
  for (var i = 0; i < stories.length; i++) {
    if (stories[i].className == 'current') {
      var j = direction == 'next' ? i+1 : i-1;
      
      if (j >= 0 || j < stories.length) {
        stories[i].className = '';
        stories[j].className = 'current';
        
        dots[i].className = '';
        dots[j].className = 'current';
        
        prev.className = (j == 0) ? 'disabled' : '';
        next.className = (j == (stories.length-1)) ? 'disabled' : '';
        
        newsEllipsizer.refresh();
      }
      
      break;
    }
  }
  
  return false;
}

function videoPaneSwitchVideo(elem, direction) {
  if (elem.className.match(/disabled/)) { return false; }

  var videos = getVideos();
  
  var dots = getVideoDots();
  var prev = document.getElementById('videoPrev');
  var next = document.getElementById('videoNext');
  
  for (var i = 0; i < videos.length; i++) {
    if (videos[i].className == 'current') {
      var j = direction == 'next' ? i+1 : i-1;
      
      if (j >= 0 || j < videos.length) {
        videos[i].className = '';
        videos[j].className = 'current';
        
        dots[i].className = '';
        dots[j].className = 'current';
        
        prev.className = (j == 0) ? 'disabled' : '';
        next.className = (j == (videos.length-1)) ? 'disabled' : '';

        videoEllipsizer.refresh();
        
      }
      
      break;
    }
  }
  
  return false;
}
