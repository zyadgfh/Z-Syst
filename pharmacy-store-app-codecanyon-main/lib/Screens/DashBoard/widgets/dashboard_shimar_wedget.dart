import 'package:flutter/material.dart';
import 'package:skeletonizer/skeletonizer.dart';
import '../../../constant.dart';
import '../../widget/acnoo_scafold.dart';
import '../components.dart';

class DashBoardShimmer extends StatelessWidget {
  const DashBoardShimmer({super.key, required this.theme});

  final ThemeData theme;

  @override
  Widget build(BuildContext context) {
    return Skeletonizer(
      enabled: true,
      child: AcnooScafoldWidget(
        appBar: AppBar(
          iconTheme: IconThemeData(color: Colors.white),
          centerTitle: true,
          backgroundColor: Colors.transparent,
          title: Text(
            '',
            style: theme.textTheme.titleLarge?.copyWith(color: kWhite, fontSize: 20),
            //'Dashboard'
          ),
          actions: [
            IconButton(
              onPressed: () {},
              icon: Icon(
                Icons.refresh,
                color: Colors.white,
              ),
            ),
          ],
        ),
        body: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 16),
          child: SingleChildScrollView(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // title
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'Overview',
                      style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
                    ),
                    Container(
                      height: 32,
                      padding: const EdgeInsets.symmetric(horizontal: 5),
                      // width: 100,
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(5),
                        border: Border.all(
                          color: kOutlineColor,
                        ),
                      ),
                      // child: DropdownButtonHideUnderline(child: getTime(ref)),
                    )
                  ],
                ),
                const SizedBox(height: 12),

                // Total customer and supplier
                Row(
                  children: [
                    Expanded(
                      child: DashBordContainerWidget(
                        title: '',
                        gradient: LinearGradient(
                          colors: [
                            Colors.grey.withValues(alpha: 0.3),
                            Colors.grey.withValues(alpha: 0.3),
                          ],
                        ),
                        //'Customer Due',
                        subtitle: '',
                      ),
                    ),
                    SizedBox(width: 12),
                    Expanded(
                      child: DashBordContainerWidget(
                          gradient: LinearGradient(
                            colors: [
                              Colors.grey.withValues(alpha: 0.3),
                              Colors.grey.withValues(alpha: 0.3),
                            ],
                          ),
                          title: '',
                          subtitle: ''),
                    ),
                  ],
                ),
                const SizedBox(height: 12),

                // Total customer and supplier
                Row(
                  children: [
                    Expanded(
                        child: DashBordContainerWidget(
                      title: '',
                      gradient: LinearGradient(
                        colors: [
                          Colors.grey.withValues(alpha: 0.3),
                          Colors.grey.withValues(alpha: 0.3),
                        ],
                      ),
                      subtitle: '',
                    )),
                    SizedBox(
                      width: 12,
                    ),
                    Expanded(
                      child: DashBordContainerWidget(
                        gradient: LinearGradient(colors: [
                          Colors.grey.withValues(alpha: 0.3),
                          Colors.grey.withValues(alpha: 0.3),
                        ]),
                        title: '',
                        subtitle: '',
                      ),
                    )
                  ],
                ),
                const SizedBox(height: 24),
                Text(
                  '',
                  style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
                ),
                const SizedBox(height: 16),
                Row(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    RichText(
                      text: TextSpan(
                        children: [
                          WidgetSpan(
                              alignment: PlaceholderAlignment.middle,
                              child: Container(
                                height: 4.61,
                                width: 8.3,
                                decoration: BoxDecoration(
                                  color: Colors.grey[300],
                                  borderRadius: BorderRadius.circular(1),
                                ),
                              )),
                          TextSpan(text: '', style: theme.textTheme.bodyMedium?.copyWith(color: kNutral700)),
                          TextSpan(
                            text: "",
                            style: theme.textTheme.titleSmall?.copyWith(
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 16),
                    RichText(
                      text: TextSpan(
                        children: [
                          WidgetSpan(
                              alignment: PlaceholderAlignment.middle,
                              child: Container(
                                height: 4.61,
                                width: 8.3,
                                decoration: BoxDecoration(
                                  color: Colors.grey[300],
                                  borderRadius: BorderRadius.circular(1),
                                ),
                              )),
                          TextSpan(text: '', style: theme.textTheme.bodyMedium?.copyWith(color: kNutral700)),
                          TextSpan(
                            text: "",
                            style: theme.textTheme.titleSmall?.copyWith(
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),

                // Loss profit Chart
                // SizedBox(height: 250, child: PurchaseSaleChart(model: snapShot)),
                const SizedBox(height: 24),
                Text(
                  '',
                  style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
                ),
                const SizedBox(height: 16),
                Row(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    RichText(
                      text: TextSpan(
                        children: [
                          WidgetSpan(
                              alignment: PlaceholderAlignment.middle,
                              child: Container(
                                height: 4.61,
                                width: 8.3,
                                decoration: BoxDecoration(
                                  color: Colors.grey[300],
                                  borderRadius: BorderRadius.circular(1),
                                ),
                              )),
                          TextSpan(text: '', style: theme.textTheme.bodyMedium?.copyWith(color: kNutral700)),
                          TextSpan(
                            text: "",
                            style: theme.textTheme.titleSmall?.copyWith(
                              fontWeight: FontWeight.w600,
                            ),
                          )
                        ],
                      ),
                    ),
                    const SizedBox(
                      width: 20,
                    ),
                    RichText(
                      text: TextSpan(
                        children: [
                          WidgetSpan(
                              alignment: PlaceholderAlignment.middle,
                              child: Container(
                                height: 4.61,
                                width: 8.3,
                                decoration: BoxDecoration(
                                  color: Colors.grey[300],
                                  borderRadius: BorderRadius.circular(1),
                                ),
                              )),
                          TextSpan(text: '', style: theme.textTheme.bodyMedium?.copyWith(color: kNutral700)),
                          TextSpan(
                            text: "",
                            style: theme.textTheme.titleSmall?.copyWith(
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                //Loss Profit Chart
                Container(
                  height: 250,
                  width: double.infinity,
                  color: Colors.grey[300],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
