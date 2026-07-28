// ignore_for_file: unused_result

import 'package:flutter/material.dart';
import 'package:flutter_feather_icons/flutter_feather_icons.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hugeicons/hugeicons.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/app_config/api_config.dart';
import 'package:mobile_pos/Screens/ledger/view_ledger.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/communication_button.dart';
import 'package:mobile_pos/Screens/widget/key_value_widget.dart';
import 'package:mobile_pos/Screens/widget/primary_button.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import 'package:url_launcher/url_launcher.dart';
import '../../currency.dart';
import '../Purchase List/purchase_details.dart';
import '../Report/model/PurchaseReportModel.dart';
import '../Report/model/sales_report_model.dart';
import '../Sales List/sale_details.dart';
import 'Model/parties_model.dart';
import 'Repo/parties_repo.dart';
import 'add_party.dart';

class CustomerDetails extends StatefulWidget {
  CustomerDetails({super.key, required this.party});
  PartyModel party;

  @override
  State<CustomerDetails> createState() => _CustomerDetailsState();
}

class _CustomerDetailsState extends State<CustomerDetails> {
  Future<void> showDeleteConfirmationAlert({
    required BuildContext context,
    required String id,
    required WidgetRef ref,
  }) async {
    return showDialog(
      context: context,
      builder: (BuildContext context1) {
        return AlertDialog(
          title: Text(
            lang.S.of(context).confirmDelete,
            //'Confirm Delete'
          ),
          content: Text(
            lang.S.of(context).areYouSureYouWant,
            //'Are you sure you want to delete this party?'
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: Text(
                lang.S.of(context).cancel,
                //'Cancel'
              ),
            ),
            TextButton(
              onPressed: () async {
                Navigator.pop(context);
                final party = PartyRepository();
                await party.deleteParty(id: id, context: context, ref: ref);
              },
              child: Text(lang.S.of(context).delete,
                  // 'Delete',
                  style: const TextStyle(color: Colors.red)),
            ),
          ],
        );
      },
    );
  }

  int selectedIndex = 0;

  // ---Controllers
  final ScrollController _scrollController = ScrollController();
  final PagingController<int, SaleData> pageController = PagingController(firstPageKey: 1);
  final PagingController<int, Datas> purchaseController = PagingController(firstPageKey: 1);

  // Purchase List Repo
  PartyRepository repo = PartyRepository();
  String? productCode;

  // ---Fetch Purchase List
  Future<void> fetchSaleDataList(int pageKey) async {
    SaleReportModel? list;
    try {
      list = await repo.getRecentSaleTransaction(nextPage: pageKey.toString(), id: widget.party.id ?? 0);

      if (list != null) {
        final newItems = list.data?.data ?? [];
        final isLastPage = list.data?.lastPage == list.data?.currentPage;

        if (isLastPage) {
          pageController.appendLastPage(newItems);
        } else {
          final nextPageKey = pageKey + 1;
          pageController.appendPage(newItems, nextPageKey);
        }
      }
    } catch (error) {
      pageController.error = error;
    }
    setState(() {});
  }

  // ---Fetch Purchase List
  Future<void> fetchPurchaseDataList(int pageKey) async {
    PurchaseReportModel? list;
    try {
      list = await repo.getPurchaseReportList(
        nextPage: pageKey.toString(),
        id: widget.party.id ?? 0,
      );

      if (list != null) {
        final newItems = list.data?.data ?? [];
        final isLastPage = list.data?.lastPage == list.data?.currentPage;

        if (isLastPage) {
          purchaseController.appendLastPage(newItems);
        } else {
          final nextPageKey = pageKey + 1;
          purchaseController.appendPage(newItems, nextPageKey);
        }
      }
    } catch (error) {
      purchaseController.error = error;
    }
    setState(() {});
  }

  bool hasFetchedPageOne = false;
  @override
  void initState() {
    super.initState();
    pageController.addPageRequestListener((pageKey) {
      if (!hasFetchedPageOne) {
        fetchSaleDataList(1);
        hasFetchedPageOne = true;
      }
    });
    purchaseController.addPageRequestListener((pageKey) {
      if (!hasFetchedPageOne) {
        fetchPurchaseDataList(1);
        hasFetchedPageOne = true;
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final language = lang.S.of(context);
    return Consumer(builder: (context, cRef, __) {
      return AcnooScafoldWidget(
        appBar: AppBar(
          backgroundColor: Colors.transparent,
          title: Text(
            widget.party.type?.toLowerCase() != 'supplier' ? lang.S.of(context).CustomerDetails : language.supplierDetails,
            style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600, fontSize: 20, color: Colors.white),
          ),
          actions: [
            IconButton(
              visualDensity: const VisualDensity(horizontal: -4, vertical: -4),
              padding: EdgeInsets.zero,
              onPressed: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => AddParty(customerModel: widget.party),
                  ),
                );
                // EditCustomer(customerModel: widget.party).launch(context);
              },
              icon: const Icon(
                FeatherIcons.edit2,
                color: kWhite,
                size: 20,
              ),
            ),
            Padding(
              padding: const EdgeInsets.only(right: 8),
              child: IconButton(
                visualDensity: const VisualDensity(horizontal: -4, vertical: -4),
                padding: EdgeInsets.zero,
                onPressed: () async {
                  await showDeleteConfirmationAlert(context: context, id: widget.party.id.toString(), ref: cRef);
                },
                icon: const Icon(
                  FeatherIcons.trash2,
                  color: kWhite,
                  size: 20,
                ),
              ),
            ),
          ],
          centerTitle: true,
          iconTheme: const IconThemeData(color: kWhite),
          elevation: 0.0,
        ),
        body: Padding(
          padding: const EdgeInsets.only(top: 6),
          child: SingleChildScrollView(
            child: Padding(
              padding: const EdgeInsets.only(top: 10, left: 16, right: 16, bottom: 16),
              child: Column(
                children: [
                  const SizedBox(height: 20),
                  Container(
                    height: 100,
                    width: 100,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      image: widget.party.image == null
                          ? const DecorationImage(
                              image: AssetImage('images/nAvatar.png'),
                              fit: BoxFit.cover,
                            )
                          : DecorationImage(
                              image: NetworkImage('${APIConfig.domain}${widget.party.image!}'),
                              fit: BoxFit.cover,
                            ),
                    ),
                  ),
                  const SizedBox(height: 10),
                  RichText(
                      text: TextSpan(text: widget.party.name ?? '', style: Theme.of(context).textTheme.bodyLarge?.copyWith(color: kNutrals900), children: [
                    TextSpan(
                      text: ' (${widget.party.type ?? ''})',
                      style: Theme.of(context).textTheme.bodyLarge?.copyWith(color: kNutrals900),
                    )
                  ])),
                  const SizedBox(height: 3),
                  Text(
                    widget.party.phone ?? '',
                    style: Theme.of(context).textTheme.bodyLarge?.copyWith(color: kNutrals900),
                  ),
                  const SizedBox(height: 20),
                  Row(
                    children: [
                      Expanded(
                          child: CommunicationButton(
                              onTap: () async {
                                var phoneNumber = widget.party.phone;
                                if (phoneNumber != null) {
                                  final Uri phoneUri = Uri(
                                    scheme: 'tel',
                                    path: phoneNumber,
                                  );
                                  if (await canLaunchUrl(phoneUri)) {
                                    await launchUrl(phoneUri);
                                  } else {
                                    throw 'Could not launch $phoneUri';
                                  }
                                }
                              },
                              icon: const HugeIcon(
                                icon: HugeIcons.strokeRoundedCall02,
                                color: Colors.black,
                                size: 24.0,
                              ),
                              title: language.call)),
                      const SizedBox(
                        width: 10,
                      ),
                      Expanded(
                          child: CommunicationButton(
                              onTap: () async {
                                final phoneNumber = widget.party.phone;

                                if (phoneNumber != null && phoneNumber.isNotEmpty) {
                                  final Uri smsUri = Uri(
                                    scheme: 'sms',
                                    path: phoneNumber,
                                    queryParameters: {'body': 'Hello'},
                                  );

                                  try {
                                    if (await canLaunchUrl(smsUri)) {
                                      await launchUrl(smsUri);
                                    } else {
                                      ScaffoldMessenger.of(context).showSnackBar(
                                        SnackBar(content: Text('Unable to open SMS client.')),
                                      );
                                    }
                                  } catch (e) {
                                    ScaffoldMessenger.of(context).showSnackBar(
                                      SnackBar(content: Text('An error occurred: $e')),
                                    );
                                  }
                                } else {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(content: Text('Phone number is invalid or missing.')),
                                  );
                                }
                              },
                              icon: const Icon(
                                Icons.chat_outlined,
                                size: 24,
                              ),
                              title: language.message)),
                      const SizedBox(
                        width: 10,
                      ),
                      Expanded(
                          child: CommunicationButton(
                              onTap: () async {
                                final emailAddress = widget.party.email;

                                if (emailAddress != null && emailAddress.isNotEmpty) {
                                  String? encodeQueryParameters(Map<String, String> params) {
                                    return params.entries.map((MapEntry<String, String> e) => '${Uri.encodeComponent(e.key)}=${Uri.encodeComponent(e.value)}').join('&');
                                  }

                                  final Uri emailLaunchUri = Uri(
                                    scheme: 'mailto',
                                    path: emailAddress,
                                    query: encodeQueryParameters(<String, String>{
                                      'subject': 'Example Subject & Symbols are allowed!',
                                    }),
                                  );

                                  launchUrl(emailLaunchUri);
                                } else {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(content: Text('Email address is invalid or missing.')),
                                  );
                                }
                              },

                              // onTap: () async {
                              //   var emailAddress = widget.party.email;
                              //   if (emailAddress != null) {
                              //     final Uri emailUri = Uri(
                              //       scheme: 'mailto',
                              //       path: emailAddress,
                              //       queryParameters: {'subject': 'Hello', 'body': 'I would like to contact you.'},
                              //     );
                              //     if (await canLaunchUrl(emailUri)) {
                              //       await launchUrl(emailUri);
                              //     } else {
                              //       throw 'Could not launch $emailUri';
                              //     }
                              //   }
                              // },
                              icon: const HugeIcon(
                                icon: HugeIcons.strokeRoundedMail01,
                                color: Colors.black,
                                size: 24.0,
                              ),
                              title: language.email)),
                    ],
                  ),
                  const SizedBox(
                    height: 20,
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '${language.personalInfo}:',
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
                      ),
                      const SizedBox(
                        height: 8,
                      ),
                      KeyValueWidget(keys: language.name, value: widget.party.name?.toString() ?? 'N/A'),
                      const SizedBox(
                        height: 8,
                      ),
                      KeyValueWidget(keys: language.phoneNumber, value: widget.party.phone?.toString() ?? 'N/A'),
                      const SizedBox(
                        height: 8,
                      ),
                      KeyValueWidget(keys: language.address, value: widget.party.address?.toString() ?? 'N/A'),
                      const SizedBox(
                        height: 8,
                      ),
                      KeyValueWidget(keys: language.status, value: widget.party.type?.toString() ?? 'N/A'),
                      const SizedBox(
                        height: 8,
                      ),
                      KeyValueWidget(keys: language.email, value: widget.party.email?.toString() ?? 'N/A'),
                      const SizedBox(
                        height: 20,
                      ),
                      Text(
                        lang.S.of(context).recentTransaction,
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(color: kNutrals900, fontWeight: FontWeight.w600),
                      ),
                      const SizedBox(height: 10),

                      // Recent Transactions
                      widget.party.type != 'Supplier'
                          ? PagedListView(
                              shrinkWrap: true,
                              padding: EdgeInsets.zero,
                              pagingController: pageController,
                              scrollController: _scrollController,
                              physics: const NeverScrollableScrollPhysics(),
                              builderDelegate: PagedChildBuilderDelegate<SaleData>(
                                newPageProgressIndicatorBuilder: (context) => SizedBox.shrink(),
                                noItemsFoundIndicatorBuilder: (context) => Padding(
                                  padding: const EdgeInsets.all(20.0),
                                  child: Center(
                                    child: Text(
                                      lang.S.of(context).noDataFound,
                                      style: theme.textTheme.bodyLarge,
                                    ),
                                  ),
                                ),
                                itemBuilder: (context, item, index) => InkWell(
                                  onTap: () => Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                      builder: (context) => SalesDetailsScreen(id: item.id ?? 0),
                                    ),
                                  ),
                                  child: Column(
                                    children: [
                                      Row(
                                        children: [
                                          RichText(
                                            text: TextSpan(
                                              text: '${language.invoice}: ',
                                              style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                              children: [
                                                TextSpan(
                                                    text: item.invoiceNumber ?? 'N/A',
                                                    style: theme.textTheme.bodyMedium?.copyWith(
                                                      color: kTitleColor,
                                                      fontWeight: FontWeight.w500,
                                                    ))
                                              ],
                                            ),
                                          ),
                                          Spacer(),
                                          RichText(
                                            text: TextSpan(
                                              text: '${language.total}: ',
                                              style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                              children: [
                                                TextSpan(
                                                  text: '$currency${item.totalAmount ?? 'N/A'}',
                                                  style: theme.textTheme.bodyMedium?.copyWith(
                                                    color: kNutrals900,
                                                    fontWeight: FontWeight.w500,
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ),
                                        ],
                                      ),
                                      Row(
                                        children: [
                                          RichText(
                                            text: TextSpan(
                                              text: '${item.party?.phone.toString()}',
                                              style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                            ),
                                          ),
                                          Spacer(),
                                          RichText(
                                            text: TextSpan(
                                              text: '${language.paid}: ',
                                              style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                              children: [
                                                TextSpan(
                                                  text: '$currency${item.paidAmount ?? 'N/A'}',
                                                  style: theme.textTheme.bodyMedium?.copyWith(
                                                    color: kNutrals900,
                                                    fontWeight: FontWeight.w500,
                                                  ),
                                                ),
                                              ],
                                            ),
                                          )
                                        ],
                                      ),
                                      Row(
                                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                        children: [
                                          RichText(
                                            text: TextSpan(
                                              text: item.saleDate != null
                                                  ? '${language.date}: ${DateFormat('dd/MM/yyyy, h:mm a').format(
                                                      DateTime.parse(item.saleDate.toString()),
                                                    )}'
                                                  : '${language.date}: N/A',
                                              style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                            ),
                                          ),
                                          RichText(
                                            text: TextSpan(
                                              text: '${language.due}: ',
                                              style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                              children: [
                                                TextSpan(
                                                  text: '$currency${item.dueAmount ?? 'N/A'}',
                                                  style: theme.textTheme.bodyMedium?.copyWith(
                                                    color: (item.dueAmount != null && item.dueAmount! > 0) ? Color(0xffFF8C34) : kNutrals900,
                                                    fontWeight: FontWeight.w500,
                                                  ),
                                                )
                                              ],
                                            ),
                                          )
                                        ],
                                      ),
                                      Divider(
                                        thickness: 1.0,
                                        color: kOutlineColor,
                                      )
                                    ],
                                  ),
                                ),
                              ),
                            )
                          : PagedListView(
                              shrinkWrap: true,
                              padding: EdgeInsets.zero,
                              pagingController: purchaseController,
                              scrollController: _scrollController,
                              physics: const NeverScrollableScrollPhysics(),
                              builderDelegate: PagedChildBuilderDelegate<Datas>(
                                newPageProgressIndicatorBuilder: (context) => const CircularProgressIndicator(color: kMainColor),
                                noItemsFoundIndicatorBuilder: (context) => Padding(
                                  padding: const EdgeInsets.all(20.0),
                                  child: Center(
                                    child: Text(
                                      lang.S.of(context).noDataFound,
                                      style: theme.textTheme.bodyLarge,
                                    ),
                                  ),
                                ),
                                itemBuilder: (context, item, index) => InkWell(
                                  onTap: () => Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                      builder: (context) => PurchaseDetails(id: item.id ?? 0),
                                    ),
                                  ),
                                  child: Column(
                                    children: [
                                      Row(
                                        children: [
                                          RichText(
                                            text: TextSpan(
                                              text: '${language.invoice}: ',
                                              style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                              children: [
                                                TextSpan(
                                                    text: item.invoiceNumber ?? 'N/A',
                                                    style: theme.textTheme.bodyMedium?.copyWith(
                                                      color: kTitleColor,
                                                      fontWeight: FontWeight.w500,
                                                    ))
                                              ],
                                            ),
                                          ),
                                          Spacer(),
                                          RichText(
                                            text: TextSpan(
                                              text: '${language.total}: ',
                                              style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                              children: [
                                                TextSpan(
                                                  text: '$currency${item.totalAmount ?? 'N/A'}',
                                                  style: theme.textTheme.bodyMedium?.copyWith(
                                                    color: kNutrals900,
                                                    fontWeight: FontWeight.w500,
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ),
                                        ],
                                      ),
                                      Row(
                                        children: [
                                          RichText(
                                            text: TextSpan(
                                              text: '${item.party?.phone.toString()}',
                                              style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                            ),
                                          ),
                                          Spacer(),
                                          RichText(
                                            text: TextSpan(
                                              text: '${language.paid}: ',
                                              style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                              children: [
                                                TextSpan(
                                                  text: '$currency${item.paidAmount ?? 'N/A'}',
                                                  style: theme.textTheme.bodyMedium?.copyWith(
                                                    color: kNutrals900,
                                                    fontWeight: FontWeight.w500,
                                                  ),
                                                ),
                                              ],
                                            ),
                                          )
                                        ],
                                      ),
                                      Row(
                                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                        children: [
                                          RichText(
                                            text: TextSpan(
                                              text: item.purchaseDate != null
                                                  ? '${language.date}: ${DateFormat('dd/MM/yyyy, h:mm a').format(
                                                      DateTime.parse(item.purchaseDate.toString()),
                                                    )}'
                                                  : '${language.date}: N/A',
                                              style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                            ),
                                          ),
                                          RichText(
                                            text: TextSpan(
                                              text: '${language.due}: ',
                                              style: theme.textTheme.bodyMedium?.copyWith(color: kNutral600),
                                              children: [
                                                TextSpan(
                                                  text: '$currency${item.dueAmount ?? 'N/A'}',
                                                  style: theme.textTheme.bodyMedium?.copyWith(
                                                    color: (item.dueAmount != null && item.dueAmount! > 0) ? Color(0xffFF8C34) : kNutrals900,
                                                    fontWeight: FontWeight.w500,
                                                  ),
                                                )
                                              ],
                                            ),
                                          )
                                        ],
                                      ),
                                      Divider(
                                        thickness: 1.0,
                                        color: kOutlineColor,
                                      )
                                    ],
                                  ),
                                ),
                              ),
                            ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
        bottomNavigationBar: Padding(
          padding: const EdgeInsets.only(left: 16, right: 16, bottom: 10, top: 10),
          child: NewPrimaryButton(
            buttonText: language.viewLedger,
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => ViewLedgerScreen(
                    id: widget.party.id ?? 0,
                    party: widget.party,
                  ),
                ),
              );
            },
          ),
        ),
      );
    });
  }
}
