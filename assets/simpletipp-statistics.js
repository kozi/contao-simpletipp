function showPlotAsImage(plotId) {
    $('#'+plotId+'Image').html(
        $('#'+plotId).jqplotToImageElem()
    );
}
