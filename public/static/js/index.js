// 热搜榜单组件
Vue.component('board-container', {
  props: ['info'],
  template: `<div :id="info.id" :title="'更新时间:'+info.updated_at" class="board-container">
    <div class="board-head">
      <img :src="info.icon" :alt="info.source" />
      <h3>{{info.source}}</h3>
      <span>{{info.title}}</span>
    </div>
    <transition-group class="board-content" name="board-content" tag="div">
      <board-item v-for="item in info.data" :item="item" :key="item.title"></board-item>
    </transition-group>
  </div>`
});

// 热搜词条组件
Vue.component('board-item', {
  props: ['item'],
  template: `<a :title="'热度：'+item.score" class="board-item" :href="item.url" target="_blank">
        <div class="order">
          <span>{{item.rank}}</span>
        </div>
        <div class="title">
          <span class="word">{{item.title}}</span>
          <span v-if="item.label!=''" class="tag" :class="tag(item.label)">{{item.label}}</span>
        </div>
        <div class="score">{{animatedScore}}</div>
      </a>`,
  data: function () {
    return {
      tweenedScore: 0
    };
  },
  computed: {
    animatedScore: function () {
      return this.tweenedScore.toFixed(0);
    }
  },
  watch: {
    item: function (newValue) {
      gsap.to(this.$data, { duration: 0.5, tweenedScore: newValue.score });
    }
  },
  mounted: function () {
    gsap.to(this.$data, { duration: 0.5, tweenedScore: this.item.score });
  },
  methods: {
    tag: function (label) {
      switch (label) {
        case '新':
          return 'tag-new';
          break;
        case '热':
          return 'tag-hot';
          break;
        case '爆':
          return 'tag-boom';
          break;
        default:
          return 'tag-other';
          break;
      }
    }
  }
});

// 通知
const notification = window.Notification || window.mozNotification || window.webkitNotification;
if (notification) {
  notification.requestPermission(function (params) {
    console.log(params);
    if ("denied" == params) {
      alert("请开启通知权限");
    }
  });
} else {
  console.log("浏览器不支持通知");
}

// 创建Vue实例
const vm = new Vue({
  el: ".container",
  data: allData,
});

// 创建WebSocket连接
const ws = new WebSocket("wss://trend.ytool.cn/wss");
ws.onopen = function (event) {
  console.log("建立WebSocket连接");
};

ws.onclose = function (event) {
  console.log("断开连接");
  alert("推送服务异常，请刷新页面重试");
};

ws.onerror = function (event) {
  console.log("连接错误");
};

ws.onmessage = function (event) {
  let result = JSON.parse(event.data);
  console.log(result);
  switch (result.code) {
    case 0:
      console.log("服务异常");
      break;
    case 200:
      console.log("WebSocket连接成功");
      const params = {
        act: "getBoardList",
      };
      ws.send(JSON.stringify(params));
      break;
    case 1000:
      console.log("操作未知");
      break;
    case 1001:
      boardList = result.data;
      boardList.forEach((element) => {
        const params = {
          act: "getBoardData",
          action: element.action,
        };
        ws.send(JSON.stringify(params));
      });
      break;
    case 1002:
      boardData = result.data;
      updateBoardList(
        boardData.action,
        boardData.list,
        boardData.updated_at
      );
      break;
    case 1100:
      boardData = result.data;
      updateBoardList(
        boardData.action,
        boardData.list,
        boardData.updated_at
      );
      break;
    default:
      break;
  }
};

// 通知列表
const noticeList = [];
/**
 * 通知 
 */
function notice(oldData, newData, id) {
  let hashTable = [];
  for (let i = 0; i < oldData.length; i++) {
    const element = oldData[i];
    hashTable.push(element.title);
  }
  for (let i = 0; i < newData.length; i++) {
    const element = newData[i];
    if (!(hashTable.includes(element.title))) {
      element['id'] = id;
      noticeList.push(element);
    }
  }
}
setInterval(function () {
  if (noticeList.length > 0) {
    const element = noticeList.shift();
    const notify = new notification(
      allData['hotBoardData'][element.id]['source'] + ' - ' + allData['hotBoardData'][element.id]['title'],
      {
        dir: 'auto', // 文字的方向；它的值可以是 auto（自动）, ltr（从左到右）, or rtl（从右到左）
        lang: 'zh-CN', // 指定通知中所使用的语言
        body: element.title, // 通知中额外显示的字符串
        tag: new Date(),  // 赋予通知一个ID，以便在必要的时候对通知进行刷新、替换或移除
        icon: 'cover' in element ? element.cover : allData['hotBoardData'][element.id]['icon'] // 将被用于显示通知的图标,可使用import引入，也可以填写图标存放路径
      })
    notify.onclick = function (params) {
      window.open(element.url);
    }
  } else {
    return;
  }
}, 1000);

/**
 * 更新榜单
 */
function updateBoardList(id, list, updated_at) {
  notice(allData['hotBoardData'][id].data, list, id);
  allData['hotBoardData'][id].data = list;
  allData['hotBoardData'][id].updated_at = updated_at;
}

function getRandomIndex(start, end) {
  return start + Math.floor((end - start) * Math.random());
}

// Echarts数据
const chartDataList = {};
const xData = [];
const yData = [];

// 生成图表数据
function makeChartData(mode = 0) {
  let sources;
  let source_key;
  let item_index;
  let item;
  // 相邻词条热度差值阈值
  let diffScore = 200000;
  switch (mode) {
    case 0:
      sources = Object.keys(allData['hotBoardData']);
      source_key = sources[getRandomIndex(0, sources.length)];
      item_index = getRandomIndex(0, allData['hotBoardData'][source_key]['data'].length);
      item = allData['hotBoardData'][source_key]['data'][item_index];
      while (xData.includes(item.title) || (yData.length > 0 && Math.abs(yData[yData.length - 1] - item.score) < diffScore)) {
        item_index = getRandomIndex(0, allData['hotBoardData'][source_key]['data'].length);
        item = allData['hotBoardData'][source_key]['data'][item_index];
      }
      xData.push(item.title);
      yData.push({
        name: item.title,
        value: item.score
      });
      chartDataList[item.title] = {
        id: source_key,
        icon: allData['hotBoardData'][source_key]['icon'],
        source: allData['hotBoardData'][source_key]['source'],
        title: allData['hotBoardData'][source_key]['title'],
        updated_at: allData['hotBoardData'][source_key]['updated_at'],
        data: item
      };
      delete chartDataList[xData.shift()];
      yData.shift();
      break;
    case 1:
      sources = Object.keys(allData['hotBoardData']);
      source_key = sources[getRandomIndex(0, sources.length)];
      item_index = getRandomIndex(0, allData['hotBoardData'][source_key]['data'].length);
      item = allData['hotBoardData'][source_key]['data'][item_index];
      while (xData.includes(item.title) || (yData.length > 0 && Math.abs(yData[yData.length - 1] - item.score) < diffScore)) {
        item_index = getRandomIndex(0, allData['hotBoardData'][source_key]['data'].length);
        item = allData['hotBoardData'][source_key]['data'][item_index];
      }
      xData.push(item.title);
      yData.push({
        name: item.title,
        value: item.score
      });
      chartDataList[item.title] = {
        id: source_key,
        icon: allData['hotBoardData'][source_key]['icon'],
        source: allData['hotBoardData'][source_key]['source'],
        title: allData['hotBoardData'][source_key]['title'],
        updated_at: allData['hotBoardData'][source_key]['updated_at'],
        data: item
      };
      break;
    case -1:
      delete chartDataList[xData.shift()];
      yData.shift();
      break;
    default:
      sources = Object.keys(allData['hotBoardData']);
      source_key = sources[getRandomIndex(0, sources.length)];
      item_index = getRandomIndex(0, allData['hotBoardData'][source_key]['data'].length);
      item = allData['hotBoardData'][source_key]['data'][item_index];
      while (xData.includes(item.title) || (yData.length > 0 && Math.abs(yData[yData.length - 1] - item.score) < diffScore)) {
        item_index = getRandomIndex(0, allData['hotBoardData'][source_key]['data'].length);
        item = allData['hotBoardData'][source_key]['data'][item_index];
      }
      xData.push(item.title);
      yData.push({
        name: item.title,
        value: item.score
      });
      chartDataList[item.title] = {
        id: source_key,
        icon: allData['hotBoardData'][source_key]['icon'],
        source: allData['hotBoardData'][source_key]['source'],
        title: allData['hotBoardData'][source_key]['title'],
        updated_at: allData['hotBoardData'][source_key]['updated_at'],
        data: item
      };
      delete chartDataList[xData.shift()];
      yData.shift();
      break;
  }
}

// Echarts实例
const myChart = echarts.init(document.getElementById("chart"));
// Echarts配置
const option = {
  tooltip: {
    trigger: 'axis',
    triggerOn: 'click',
    enterable: true,
    padding: [8, 15],
    backgroundColor: "#fff",
    borderColor: "#333",
    textStyle: {
      color: "#333"
    },
    formatter: function (params) {
      console.log(params);
      chartUpdateSwitch = false;
      let name = params[0].data.name;
      let htmlStr = '<div>';
      htmlStr += '<span style="font-size: 16px; font-weight: 800;">' + name + '</span>';
      if (chartDataList[name]['data']['label'] != '') {
        htmlStr += '<span style="padding: 0 4px; line-height: 20px; font-size: 13px; border-radius: 4px; color: #fff; background-color: #f06010; margin-left: 8px;">' + chartDataList[name]['data']['label'] + '</span>';
      }
      htmlStr += '<br>';
      htmlStr += '<span style="margin-top: 12px; display: inline-block; width: 12px; height: 12px; background-color: #A35AE0; border-radius: 20px; margin-right: 12px;"></span><span>排名：' + chartDataList[name]['data']['rank'] + '</span><br>';
      htmlStr += '<span style="margin-top: 12px; display: inline-block; width: 12px; height: 12px; background-color: #00A0E4; border-radius: 20px; margin-right: 12px;"></span><span>热度：' + chartDataList[name]['data']['score'] + '</span><br>';
      htmlStr += '<span style="margin-top: 12px; display: inline-block; width: 12px; height: 12px; background-color: #7083db; border-radius: 20px; margin-right: 12px;"></span><span>平台：<img style="width: 20px; height: 20px; margin-right: 4px; vertical-align: bottom;" src="' + chartDataList[name]['icon'] + '">' + chartDataList[name]['source'] + '</span><br>';
      htmlStr += '<span style="margin-top: 12px; display: inline-block; width: 12px; height: 12px; background-color: #0CEBEA; border-radius: 20px; margin-right: 12px;"></span><span>榜单：' + chartDataList[name]['title'] + '</span><br>';
      htmlStr += '<button style="padding: 4px 8px; background-color: #4c6cfb; font-size: 12px; color: #fff;border: none;border-radius: 4px; margin-top: 12px; cursor: pointer;" onclick="window.open(\'' + chartDataList[name]['data']['url'] + '\')">查看</button>';
      htmlStr += '</div>';
      return htmlStr;
    },
  },
  legend: {
    show: false
  },
  color: '#0887FF',
  grid: {
    left: '5%',
    right: '5%',
    top: '8%',
    bottom: '6%',
    containLabel: true
  },
  xAxis: {
    type: 'category',
    show: false,
    axisTick: {
      show: false,
    },
    axisLine: {
      show: false
    },
    axisLabel: {
      textStyle: {
        color: '#999',
      }
    },
  },
  yAxis: {
    type: 'value',
    axisTick: {
      show: false,
    },
    splitLine: {
      lineStyle: {
        type: "dotted"
      }
    },
    axisLabel: {
      show: false,
    },
    axisLine: {
      show: false
    }
  },
  series: [
    {
      type: 'line',
      smooth: true,
      areaStyle: {
        color: new echarts.graphic.LinearGradient(
          0, 1, 0, 0,
          [
            { offset: 1, color: '#00A0E4' },
            { offset: 0, color: '#fbfdfe' },
          ]
        )
      },
      itemStyle: {
        emphasis: {
          cursor: 'pointer'
        }
      },
      label: {
        show: true,
        position: 'top',
        textStyle: {
          color: '#333',
        },
        fontSize: 12,
        lineHeight: 18,
        align: 'left',
        formatter: function (params) {
          let name = params.data.name;
          return name;
        }
      }
    }
  ]
};

const chartItemWidth = 80;
// 图表展示词条数量，992是容器最大宽度
let chartDataCount = Math.min(Math.floor(992 / chartItemWidth), Math.max(4, Math.floor(window.innerWidth / chartItemWidth)));
for (let i = 0; i < chartDataCount; i++) {
  makeChartData(1);
}

myChart.setOption(option);

myChart.on('globalout', function (params) {
  chartUpdateSwitch = true;
});

// 图表是否继续更新
let chartUpdateSwitch = true;
setInterval(function () {
  if (chartUpdateSwitch == false) return;
  if (xData.length == chartDataCount) {
    makeChartData(0);
  }
  else if (xData.length > chartDataCount) {
    makeChartData(-1);
  } else {
    makeChartData(1);
  }
  myChart.setOption({
    xAxis: {
      data: xData
    },
    series: [{
      type: 'line',
      data: yData
    }]
  });
}, 1600);

// WebSocket心跳包30S
setInterval(function () {
  const params = {
    act: "ping"
  };
  ws.send(JSON.stringify(params));
}, 30000);

window.addEventListener('resize', function () {
  chartDataCount = Math.min(Math.floor(992 / chartItemWidth), Math.max(4, Math.floor(window.innerWidth / chartItemWidth)));
  myChart.resize();
});