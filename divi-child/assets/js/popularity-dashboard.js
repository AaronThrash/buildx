(function() {
  'use strict';

  if (!window.buildxPopAnalytics) return;

  var data    = window.buildxPopAnalytics;
  var select  = document.getElementById('buildx-pop-dataset');
  var canvas  = document.getElementById('buildx-pop-chart');
  var topListContainer = document.getElementById('buildx-pop-top-list'); 
  
  // *** CRITICAL CHECK: ensure all required elements exist ***
  if (!canvas || !canvas.getContext || !topListContainer) {
      // If the container is missing, we must ensure the placeholder is cleared 
      // if possible, but mainly, we must stop the script.
      return; 
  }
  
  var ctx = canvas.getContext('2d');

  // Basic margin & style settings
  var paddingLeft   = 50;
  var paddingRight  = 20;
  var paddingTop    = 20;
  var paddingBottom = 40;

  function getDataset(key) {
    return data[key] || { labels: [], views: [], clicks: [], top_posts: [] };
  }

  function maxOfArrays(a, b) {
    var max = 0;
    for (var i = 0; i < a.length; i++) {
      if (a[i] > max) max = a[i];
    }
    for (var j = 0; j < b.length; j++) {
      if (b[j] > max) max = b[j];
    }
    return max;
  }

  function drawAxis(ctx, width, height) {
    ctx.clearRect(0, 0, width, height);

    ctx.strokeStyle = '#ccc';
    ctx.lineWidth   = 1;

    // X axis
    ctx.beginPath();
    ctx.moveTo(paddingLeft, height - paddingBottom);
    ctx.lineTo(width - paddingRight, height - paddingBottom);
    ctx.stroke();

    // Y axis
    ctx.beginPath();
    ctx.moveTo(paddingLeft, paddingTop);
    ctx.lineTo(paddingLeft, height - paddingBottom);
    ctx.stroke();
  }

  function drawLine(ctx, points, color, dashed) {
    if (points.length < 2) return;
    ctx.save();
    ctx.strokeStyle = color;
    ctx.lineWidth   = 2;
    if (dashed && ctx.setLineDash) {
      ctx.setLineDash([5, 4]);
    } else if (ctx.setLineDash) {
      ctx.setLineDash([]);
    }

    ctx.beginPath();
    ctx.moveTo(points[0].x, points[0].y);
    for (var i = 1; i < points.length; i++) {
      ctx.lineTo(points[i].x, points[i].y);
    }
    ctx.stroke();
    ctx.restore();
  }

  function drawLabels(ctx, labels, width, height) {
    ctx.fillStyle = '#444';
    ctx.font = '11px system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif';
    ctx.textAlign = 'center';

    var innerWidth = width - paddingLeft - paddingRight;
    var count      = labels.length;
    if (count < 2) return;

    var stepX = innerWidth / (count - 1);
    var baselineY = height - paddingBottom + 15;

    // Show at most ~10 labels to avoid clutter
    var maxLabels = 10;
    var skip = Math.ceil(count / maxLabels);

    for (var i = 0; i < count; i++) {
      if (i % skip !== 0) continue;
      var x = paddingLeft + i * stepX;
      var label = labels[i];
      // Show only MM-DD for compactness
      var shortLabel = label.slice(5);
      ctx.fillText(shortLabel, x, baselineY);
    }
  }

  function drawYAxisTicks(ctx, maxValue, width, height) {
    ctx.fillStyle = '#444';
    ctx.font = '11px system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif';
    ctx.textAlign = 'right';

    var ticks = 4; // number of horizontal grid lines
    if (maxValue <= 0) return;

    for (var i = 0; i <= ticks; i++) {
      var ratio = i / ticks;
      var yVal  = maxValue * (1 - ratio);
      var yPos  = paddingTop + (height - paddingTop - paddingBottom) * ratio;

      // grid line
      ctx.strokeStyle = '#eee';
      ctx.lineWidth   = 1;
      ctx.beginPath();
      ctx.moveTo(paddingLeft, yPos);
      ctx.lineTo(width - paddingRight, yPos);
      ctx.stroke();

      // label
      ctx.fillText(Math.round(yVal), paddingLeft - 5, yPos + 4);
    }
  }

  // NEW: Function to render the top posts list
  function renderTopList(topPosts) {
    topListContainer.innerHTML = ''; // Clear previous content

    if (!topPosts || topPosts.length === 0) {
      topListContainer.innerHTML = '<div style="padding: 10px; text-align: center; color: #888;">No content has registered a popularity score yet.</div>';
      return;
    }

    var html = '<table class="wp-list-table widefat fixed striped">';
    html += '<thead><tr><th>#</th><th>Post Title</th><th style="width:100px; text-align:right;">Popularity Score</th></tr></thead>';
    html += '<tbody>';

    topPosts.forEach(function(post, index) {
      html += '<tr>';
      html += '<td>' + (index + 1) + '</td>';
      html += '<td><a href="' + post.permalink + '" target="_blank">' + post.title + '</a></td>';
      html += '<td style="text-align:right;"><strong>' + post.score.toLocaleString() + '</strong></td>';
      html += '</tr>';
    });

    html += '</tbody></table>';
    topListContainer.innerHTML = html;
  }

  function render(key) {
    var ds = getDataset(key);
    var labels = ds.labels || [];
    var views  = ds.views  || [];
    var clicks = ds.clicks || [];

    var width  = canvas.width;
    var height = canvas.height;

    drawAxis(ctx, width, height);

    var maxVal = maxOfArrays(views, clicks);
    if (maxVal <= 0) {
      // Still draw axes and labels, but nothing else
      drawLabels(ctx, labels, width, height);
      renderTopList(ds.top_posts); // Render empty/no data list
      return;
    }

    drawYAxisTicks(ctx, maxVal, width, height);

    var innerWidth  = width - paddingLeft - paddingRight;
    var innerHeight = height - paddingTop - paddingBottom;
    var count       = labels.length;

    var pointsViews  = [];
    var pointsClicks = [];

    if (count > 1) {
      var stepX = innerWidth / (count - 1);

      for (var i = 0; i < count; i++) {
        var ratioX = i / (count - 1);
        var x = paddingLeft + ratioX * innerWidth;

        var v = views[i] || 0;
        var c = clicks[i] || 0;

        var yV = paddingTop + (1 - (v / maxVal)) * innerHeight;
        var yC = paddingTop + (1 - (c / maxVal)) * innerHeight;

        pointsViews.push({ x: x, y: yV });
        pointsClicks.push({ x: x, y: yC });
      }
    }

    // Draw lines
    drawLine(ctx, pointsViews,  '#2563eb', false); // blue
    drawLine(ctx, pointsClicks, '#f97316', true);  // orange dashed

    drawLabels(ctx, labels, width, height);

    // Legend
    ctx.fillStyle = '#2563eb';
    ctx.fillRect(width - 150, paddingTop + 4, 10, 3);
    ctx.fillStyle = '#444';
    ctx.font = '11px system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif';
    ctx.textAlign = 'left';
    ctx.fillText('Views', width - 135, paddingTop + 8);

    ctx.strokeStyle = '#f97316';
    if (ctx.setLineDash) ctx.setLineDash([5, 4]);
    ctx.beginPath();
    ctx.moveTo(width - 150, paddingTop + 20);
    ctx.lineTo(width - 140, paddingTop + 20);
    ctx.stroke();
    if (ctx.setLineDash) ctx.setLineDash([]);
    ctx.fillStyle = '#444';
    ctx.fillText('Clicks', width - 135, paddingTop + 24);

    // Render the Top 5 Posts list
    renderTopList(ds.top_posts);
  }

  // Initial render for the default selected dataset ('lc')
  render('lc');

  if (select) {
    select.addEventListener('change', function() {
      var key = select.value || 'lc';
      render(key);
    });
  }
})();