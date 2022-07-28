window.onload = function() {
  const randomNum = () => Math.floor(Math.random() * (235 - 52 + 1) + 52);
  const randomRGB = () => `rgb(${randomNum()}, ${randomNum()}, ${randomNum()})`;
  
  var dataa = global.data;
  var count = dataa.length;
  var max = dataa[count-1];

  const lab = [];
  const arr =[];

  for(var i=1; i<=max ; i++){
    lab.push("Attempt " + i);
  }

  for(var i=0 ; i<(count-2) ; i++){
    arr.push({
      label: dataa[count-2][i],
      data: dataa[i],
      backgroundColor: randomRGB(),
      borderColor: [
          'rgba(255, 99, 132, 1)',
          'rgba(54, 162, 235, 1)',
          'rgba(255, 206, 86, 1)',
          'rgba(75, 192, 192, 1)',
          'rgba(153, 102, 255, 1)',
          'rgba(255, 159, 64, 1)'
      ],
      borderWidth: 1
    });
  }

  var ctx = document.getElementById("canvas").getContext("2d");
  const myChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: lab,
        datasets: arr,
    },
    options: {
      layout: {
        padding: {
            right: -500,
            top: 20,
            bottom: 0
        },
    },
      plugins: {
        legend: {
          display: true,
          position: 'left',
          labels: {
            fontColor: "#000080",
          }
        },
        scales: {
          y: {
            min: 0
          }
        },
      },
    }
});
};

jQuery(".reset").click(function(event){
    var elem = event.target;
    var time = jQuery( elem ).data( 'quiz-time' );

    jQuery.ajax({
      type:"POST",
      url:ajax_object.ajax_url,
      data: {action:'reset', timee: time},
      success:function(res){
        window.location.reload();
      }
    });
  
});