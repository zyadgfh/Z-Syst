import 'package:flutter/material.dart';
import 'package:iconly/iconly.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import '../../../constant.dart';

Future<bool> showDeleteAlert({required String itemsName, required BuildContext context}) async {
  return await showDialog(
      barrierDismissible: false,
      context: context,
      builder: (BuildContext dialogContext) {
        return Padding(
          padding: const EdgeInsets.all(20.0),
          child: Center(
            child: Container(
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.all(
                  Radius.circular(15),
                ),
              ),
              child: Padding(
                padding: const EdgeInsets.all(20.0),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.center,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      '${lang.S.of(context).areYouSureYouWantToDeleteThis} $itemsName?',
                      style: TextStyle(color: kTitleColor, fontSize: 18.0, fontWeight: FontWeight.bold),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 30),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        SizedBox(
                          width: 130,
                          child: OutlinedButton(
                            onPressed: () {
                              Navigator.of(context).pop(false);
                            },
                            style: ButtonStyle(
                              side: WidgetStateProperty.all(
                                BorderSide(color: Colors.red),
                              ),
                              shape: WidgetStateProperty.all(
                                RoundedRectangleBorder(borderRadius: BorderRadius.circular(8.0), side: const BorderSide(color: Colors.red)),
                              ),
                              overlayColor: WidgetStateProperty.all<Color>(
                                Colors.red.withValues(alpha: 0.1),
                              ),
                              shadowColor: WidgetStateProperty.all<Color>(Colors.red.withValues(alpha: 0.1)),
                              minimumSize: WidgetStateProperty.all<Size>(
                                const Size(150, 50),
                              ),
                              backgroundColor: WidgetStateProperty.all<Color>(kWhite),

                              // Change background color
                              textStyle: WidgetStateProperty.all<TextStyle>(const TextStyle(color: Colors.white)), // Change text color
                              // Add more properties as needed
                            ),
                            child: Text(
                              lang.S.of(context).cancel,
                              style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold, fontSize: 16),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ),
                        const SizedBox(width: 14),
                        SizedBox(
                          width: 130,
                          child: ElevatedButton(
                            onPressed: () {
                              Navigator.of(context).pop(true);
                            },
                            style: ButtonStyle(
                              shape: WidgetStateProperty.all(
                                RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(8.0),
                                ),
                              ),
                              overlayColor: WidgetStateProperty.all<Color>(
                                kWhite.withValues(alpha: 0.1),
                              ),
                              shadowColor: WidgetStateProperty.all<Color>(kMainColor.withValues(alpha: 0.1)),
                              minimumSize: WidgetStateProperty.all<Size>(Size(150, 50)),
                              backgroundColor: WidgetStateProperty.all<Color>(kMainColor),
                              // Change background color
                              textStyle: WidgetStateProperty.all<TextStyle>(TextStyle(color: Colors.white)), // Change text color
                              // Add more properties as needed
                            ),
                            child: Text(
                              lang.S.of(context).delete,
                              //'Delete',
                              style: TextStyle(color: kWhite, fontWeight: FontWeight.bold, fontSize: 16), maxLines: 1, overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ),
                      ],
                    )
                  ],
                ),
              ),
            ),
          ),
        );
      });
}

class ListCardWidget extends StatelessWidget {
  const ListCardWidget({
    super.key,
    this.onEdit,
    this.onDelete,
    required this.title,
  });

  final void Function()? onEdit;
  final void Function()? onDelete;
  final String title;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 24.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Flexible(
            child: Text(
              title,
              maxLines: 3,
              overflow: TextOverflow.ellipsis,
              style: theme.textTheme.bodyLarge,
            ),
          ),
          // const Spacer(),
          Row(
            children: [
              IconButton.filledTonal(
                onPressed: onEdit, // Directly use the onEdit function
                style: IconButton.styleFrom(
                  padding: EdgeInsets.zero,
                  backgroundColor: Colors.white.withValues(alpha: 0.25),
                ),
                visualDensity: VisualDensity(horizontal: -2, vertical: -2),
                iconSize: 20,
                icon: Icon(
                  IconlyLight.edit,
                  color: kMainColor,
                ),
              ),
              IconButton.filledTonal(
                onPressed: onDelete, // Directly use the onDelete function
                style: IconButton.styleFrom(
                  padding: EdgeInsets.zero,
                  backgroundColor: Colors.white.withValues(alpha: 0.25),
                ),
                visualDensity: VisualDensity(horizontal: -2, vertical: -2),
                iconSize: 20,
                icon: Icon(
                  IconlyLight.delete,
                  color: Colors.redAccent,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
