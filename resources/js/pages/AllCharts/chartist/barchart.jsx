import React from "react"
// import getChartColorsArray from "../../../components/Common/ChartsDynamicColor";
import ChartistGraph from "react-chartist"

const Barchart = ({dataColors}) => {
  // var barChartistColors =  getChartColorsArray(dataColors); 

  var barChartData = {
    labels: [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "Mai",
      "Jun",
      "Jul",
      "Aug",
      "Sep",
      "Oct",
      "Nov",
      "Dec",
    ],
    series: [
      [5, 4, 3, 7, 5, 10, 3, 4, 8, 10, 6, 8],
      [3, 2, 9, 5, 4, 6, 4, 6, 7, 8, 7, 4],
    ], 
    // color: barChartistColors,  
  }
  var barChartOptions = {
    low: 0,
    showArea: true,
    seriesBarDistance: 10,    
  }

  return (
    <React.Fragment>
      <ChartistGraph
        style={{ height: "300px" }}
        data={barChartData}
        options={barChartOptions}
        type={"Bar"}
      />
    </React.Fragment>
  )
}

export default Barchart
