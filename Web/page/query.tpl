{extends file='common.tpl'}
{block name=body}
    <div class="container">
        <div class="card">
            {$title}
            <hr>
            <h3>{$exam.name} <small>查询结果</small></h3>
            <h5>概览</h5>
            <p>[考试] {$exam.name}@{$exam.time}</p>
            <p>[姓名] {$student.name}</p>
            <p>[考号] {$student.id}</p>
            <p>[班级] {$student.class}</p>
            <h5>各科成绩</h5>
            <div class="row">
                <div class="col-sm-6 col-xs-12">
                    {foreach $score as $name => $subject}
                        <p><strong>{$name}</strong></p>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <tbody>
                                <tr>
                                    <th>题目</th>
                                    <th>得分</th>
                                </tr>
                                {assign var="score_count" value="0"}
                                {foreach $subject as $name => $question}
                                    {assign var="score_count" value=$score_count+$question}
                                    <tr>
                                        <td>{$name}</td>
                                        <td><code>{$question}</code></td>
                                    </tr>
                                {/foreach}
                                <tr>
                                    <td><b>总计</b></td>
                                    <td><b>{$score_count}</b></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    {/foreach}
                </div>
            </div>
        </div>
    </div>
{/block}