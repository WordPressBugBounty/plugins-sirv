jQuery(function ($) {
  $(document).ready(function () {
    $("input[name=sirv_troubleshooting_ignore_issue]").on(
      "change",
      updateIgnoreIssues
    );
    function updateIgnoreIssues() {
      let ignoreIssues = {};
      let count = 0;
      const $issues = $("input[name=sirv_troubleshooting_ignore_issue]");
      $issues.each(function () {
        const id = $(this).attr("id");
        const isChecked = $(this).is(":checked");

        if (!isChecked) count++;

        ignoreIssues[id] = isChecked ? "ignore" : "active";
      });

      updateTroubleshootingCount(count);

      $data_str = JSON.stringify(ignoreIssues);
      localStorage.setItem("sirvTroubleshooting", $data_str);
    }

    function loadTroubleshootingIssuesStatusData() {
      const $issues = $("input[name=sirv_troubleshooting_ignore_issue]");
      const issuesStatus = JSON.parse(
        localStorage.getItem("sirvTroubleshooting")
      );
      let count = 0;

      if (!!issuesStatus) {
        $issues.each(function () {
          const id = $(this).attr("id");
          if (issuesStatus[id] == "ignore") {
            $(this).prop("checked", true);
          } else {
            count++;
          }
        });
        updateTroubleshootingCount(count);
      }
    }

    function updateTroubleshootingCount(issuesNum) {
      const activeIssuesClass = "sirv-active-issues";
      const $countBlock = $(".sirv-troubleshooting-count");

      $(".sirv-troubleshooting-save-issues-status").prop("disabled", false);

      if (issuesNum > 0) {
        $countBlock.text(issuesNum);
        $countBlock.addClass(activeIssuesClass);
      } else {
        $countBlock.text("");
        $countBlock.removeClass(activeIssuesClass);
      }
    }

    $(".sirv-troubleshooting-save-issues-status").on(
      "click",
      saveTroubleshootingIssuesStatus
    );
    function saveTroubleshootingIssuesStatus() {
      $data = localStorage.getItem("sirvTroubleshooting") || JSON.stringify([]);

      $.ajax({
        url: ajaxurl,
        data: {
          action: "sirv_save_troubleshooting_issues_status",
          _ajax_nonce: sirv_options_data.ajaxnonce,
          status_data: $data,
        },
        type: "POST",
        dataType: "json",
        beforeSend: function () {
          $(".sirv-backdrop").show();
        },
      })
        .done(function (res) {
          //debug
          //console.log(res);
          $(".sirv-backdrop").hide();

          if (res.error) {
            toastr.error(`Error: ${res.error}`, "", {
              preventDuplicates: true,
              timeOut: 4000,
              positionClass: "toast-top-center",
            });
          } else {
            $(".sirv-troubleshooting-save-issues-status").prop(
              "disabled",
              true
            );
            toastr.success(`Data was saved`, "", {
              preventDuplicates: true,
              timeOut: 4000,
              positionClass: "toast-top-center",
            });
          }
        })
        .fail(function (jqXHR, status, error) {
          $(".sirv-backdrop").hide();
          console.error("Error during ajax request: " + error);
          toastr.error(`Ajax error: ${error}`, "", {
            preventDuplicates: true,
            timeOut: 4000,
            positionClass: "toast-top-center",
          });
        });
    }
  }); //domready end
});
