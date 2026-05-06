# CHAPTER IV
## SUMMARY OF FINDINGS, CONCLUSION, AND RECOMMENDATIONS

This chapter provides a final synthesis of the CapTrack project, drawing upon the data gathered during the software testing and evaluation phases. It details the actual outcomes of deploying a centralized web-based management system within an academic capstone environment. By examining the results of development, usability, and performance testing, this chapter determines the overall success of the platform and outlines actionable suggestions for the system's future evolution.

### Findings

The comprehensive testing of the CapTrack application yielded highly positive results, proving its capability to handle complex academic workflows. Based on the usability testing conducted with 16 participants, the system achieved exceptional satisfaction ratings, averaging between 4.53 and 4.75 out of 5 across all metrics. Users strongly agreed that the Kanban tracking dashboard was highly intuitive (4.75) and that the application remained incredibly stable even during intensive backend operations like 100-row CSV data imports (4.62). 

Performance testing further validated the efficiency of the underlying architecture. The automated conflict-resolution algorithm (Auto-Assign) demonstrated remarkable speed, filtering out scheduling conflicts in an average of 0.25 seconds and sorting available faculty in 0.16 seconds. Furthermore, the integration of Eloquent ORM to handle dynamic document versioning proved highly reliable, allowing students to seamlessly upload proposals without overwriting previous iterations. 

While the system proved highly effective, the testing phase did reveal minor areas for optimization. Because CapTrack relies on large, multi-part form data for document transfers, participants noted slight network latency when downloading massive, historical proposal versions over slower internet connections. Additionally, while the dynamic JSON-based grading rubrics provide immense flexibility for panelists, rendering highly complex nested arrays does require optimized database querying to maintain peak dashboard speed.

### Conclusion

The development of CapTrack marks a successful digital transformation of the capstone project lifecycle. By transitioning away from traditional, fragmented methods—such as scattered email threads, physical paper grading sheets, and manual trial-and-error scheduling—the institution can now rely on a unified, impenetrable, role-based ecosystem. 

The application successfully met all of its core objectives. It effectively isolates distinct user privileges through Multi-Guard authentication, allowing Chairpersons, Coordinators, Advisers, and Students to interact securely within their designated boundaries. The implementation of real-time milestone tracking, threaded document feedback, and an intelligent Auto-Assign scheduling matrix significantly reduces the administrative burden on the faculty while empowering students to manage their research proactively. Ultimately, CapTrack serves as a highly robust, defense-ready platform that significantly enhances academic coordination, transparency, and data preservation.

### Recommendations

To further enhance the capabilities and institutional value of the CapTrack system, the researchers recommend the following future developments:

1. **Integration of Plagiarism Detection APIs:** Future iterations should consider directly linking the Document Versioning Service with third-party software like Turnitin. This would provide advisers with automated similarity reports the moment a student uploads a new proposal version.
2. **Advanced Institutional Analytics:** While the current system tracks group progress well, future updates could implement predictive analytics on the Chairperson’s dashboard. Utilizing data visualization libraries to map out historical trends in faculty workload, common bottleneck phases in student research, and overall passing rates would be highly beneficial for administrative planning.
3. **Dedicated Mobile Application:** Although the web platform is accessible via browsers, developing a dedicated mobile application (or a Progressive Web App) would greatly assist faculty members. Push notifications sent directly to their mobile devices regarding schedule approvals, direct messages, or impending grading deadlines would significantly improve response times.
4. **Enhanced Data Caching for Document Retrieval:** To address the minor latency identified during large file downloads, implementing advanced backend caching mechanisms (such as Redis) could optimize file retrieval speeds, particularly for users on less stable network connections.
5. **AI-Assisted Topic Generation:** Integrating an artificial intelligence module to cross-reference the university's archive of completed capstone projects. This could automatically flag newly proposed titles that are too similar to past works, or assist students in generating novel research gaps.
