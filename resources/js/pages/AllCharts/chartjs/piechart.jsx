import React from "react"
import { Pie } from "react-chartjs-2"
import getChartColorsArray from "../../../components/Common/ChartsDynamicColor";
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);


const PieChart = ({dataColors}) => {
  var pieChartColors =  getChartColorsArray(dataColors); 
  const data = {
    labels: ["Desktops", "Tablets"],
    datasets: [
      {
        data: [300, 180],
        backgroundColor: pieChartColors,
        hoverBackgroundColor: pieChartColors,
        hoverBorderColor: "#fff",
      },
    ],
  }

  return <Pie width={751} height="260px" data={data} className="chartjs-render-monitor mx-auto"/>
}

export default PieChart;